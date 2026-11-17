<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollPackage;
use App\Models\PayrollPackageApplication;
use App\Services\Payroll\PackageApplier;
use App\Services\Payroll\PackageCatalog;
use App\Services\Payroll\PackageSchema;
use App\Services\Payroll\PayrollSetupBadge;
use Livewire\Component;

/**
 * « Démarrage rapide » : l'entreprise choisit un paquet de son secteur, voit un aperçu de ce qui sera créé ou ignoré,
 * puis l'applique en un clic. Application non destructive : rien n'est jamais écrasé.
 */
class Packages extends Component
{
    use PayrollGuards;

    public ?int $selectedId = null;
    public bool $ack = false;
    public string $flash = '';

    /** Rapport de la dernière application, pour l'affichage. @var array<string, mixed>|null */
    public ?array $result = null;

    public function mount(): void
    {
        $this->allow('read', 'payroll_settings');
    }

    public function select(int $id): void
    {
        abort_if($this->offeredPackage($id) === null, 404);

        $this->selectedId = $id;
        $this->ack = false;
        $this->result = null;
        $this->flash = '';
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->selectedId = null;
        $this->ack = false;
        $this->resetErrorBag();
    }

    public function apply(): void
    {
        $package = $this->selectedId ? $this->offeredPackage($this->selectedId) : null;
        abort_if($package === null, 404);

        $missing = $this->missingRights($package);

        if ($missing !== []) {
            $this->flash = 'Votre profil ne permet pas d\'appliquer ce paquet. Droits manquants : ' . implode(', ', $missing) . '.';

            return;
        }

        if (! $package->isValidated() && ! $this->ack) {
            $this->addError('ack', 'Cochez la case pour confirmer que vous avez compris que ce paquet n\'est pas encore validé par un comptable.');

            return;
        }

        $plan = PackageApplier::apply($package, $this->companyId(), auth()->id());

        $this->audit('create', 'payroll_packages', $package->id, null, ['package' => $package->code, 'version' => $package->version, 'created' => $plan['totals']['created'], 'skipped' => $plan['totals']['skipped']]);
        PayrollSetupBadge::flush();

        $this->result = ['name' => $package->name, 'version' => $package->version, 'lines' => $this->describePlan($plan), 'totals' => $plan['totals']];
        $this->flash = $plan['totals']['created'] > 0
            ? 'Paquet appliqué : ' . $plan['totals']['created'] . ' élément(s) créé(s), ' . $plan['totals']['skipped'] . ' déjà présent(s) et laissé(s) tel(s) quel(s).'
            : 'Rien à créer : tout ce que contient ce paquet existe déjà chez vous. Rien n\'a été modifié.';
        $this->selectedId = null;
        $this->ack = false;
    }

    /** @return array<int, PayrollPackage> paquets proposés : dernière version validée de chaque code, sinon dernière « à valider » */
    protected function offered(): array
    {
        $rows = [];
        $models = [];

        foreach (PayrollPackage::orderBy('code')->orderBy('version')->get() as $p) {
            $rows[] = ['id' => $p->id, 'code' => $p->code, 'version' => (int) $p->version, 'status' => $p->status, 'empty' => PackageSchema::isEmpty($p->payload)];
            $models[$p->id] = $p;
        }

        return array_map(fn ($row) => $models[$row['id']], PackageCatalog::offered($rows));
    }

    protected function offeredPackage(int $id): ?PayrollPackage
    {
        foreach ($this->offered() as $p) {
            if ($p->id === $id) {
                return $p;
            }
        }

        return null;
    }

    /** @return array<int, string> noms des droits qu'il manque pour appliquer ce paquet */
    protected function missingRights(PayrollPackage $package): array
    {
        $labels = ['pay_items' => 'rubriques', 'pay_profiles' => 'modèles de traitement', 'salary_grids' => 'grilles salariales', 'payroll_settings' => 'paramètres de paie'];
        $missing = [];

        foreach (PackageSchema::requiredResources($package->payload) as $resource) {
            if (! $this->allowed('update', $resource)) {
                $missing[] = $labels[$resource] ?? $resource;
            }
        }

        return $missing;
    }

    /**
     * Phrases lisibles d'un plan (aperçu avant, rapport après).
     *
     * @param array<string, mixed> $plan
     *
     * @return array<int, array{kind: string, text: string}> kind : create | skip
     */
    protected function describePlan(array $plan): array
    {
        $lines = [];

        foreach ($plan['items']['create'] as $r) {
            $lines[] = ['kind' => 'create', 'text' => 'Rubrique ' . $r['code'] . ' : ' . $r['label']];
        }

        foreach ($plan['items']['skip'] as $r) {
            $lines[] = ['kind' => 'skip', 'text' => 'Rubrique ' . $r['code'] . ' : ' . $r['label'] . ' (existe déjà)'];
        }

        foreach ($plan['profiles']['create'] as $r) {
            $lines[] = ['kind' => 'create', 'text' => 'Modèle de traitement : ' . $r['name'] . ' (' . count($r['lines']) . ' rubrique(s), sans montant)'];
        }

        foreach ($plan['profiles']['skip'] as $r) {
            $lines[] = ['kind' => 'skip', 'text' => 'Modèle de traitement : ' . $r['name'] . ' (existe déjà)'];
        }

        foreach ($plan['grids']['create'] as $r) {
            $lines[] = ['kind' => 'create', 'text' => 'Grille salariale : ' . $r['name'] . ' (' . count($r['grades']) . ' catégorie(s))'];
        }

        foreach ($plan['grids']['skip'] as $r) {
            $lines[] = ['kind' => 'skip', 'text' => 'Grille salariale : ' . $r['name'] . ' (existe déjà)'];
        }

        if ($plan['template']['action'] === 'create') {
            $lines[] = ['kind' => 'create', 'text' => 'Modèle de bulletin : ' . $plan['template']['row']['name'] . ($plan['template']['make_default'] ? ' (devient le modèle par défaut)' : ' (votre modèle par défaut actuel est conservé)')];
        } elseif ($plan['template']['action'] === 'skip') {
            $lines[] = ['kind' => 'skip', 'text' => 'Modèle de bulletin : ' . $plan['template']['row']['name'] . ' (existe déjà)'];
        }

        if ($plan['settings']['action'] === 'fill') {
            $lines[] = ['kind' => 'create', 'text' => 'Nom de la convention : ' . $plan['settings']['convention_name']];
        } elseif ($plan['settings']['action'] === 'skip') {
            $lines[] = ['kind' => 'skip', 'text' => 'Nom de la convention (déjà renseigné chez vous, conservé)'];
        }

        return $lines;
    }

    public function render()
    {
        $companyId = $this->companyId();
        $cards = [];

        foreach ($this->offered() as $p) {
            $c = PackageSchema::counts($p->payload);
            $parts = [];

            foreach ([['items', 'rubrique(s)'], ['profiles', 'modèle(s) de traitement'], ['grids', 'grille(s)'], ['template', 'modèle de bulletin']] as [$key, $label]) {
                if ($c[$key] > 0) {
                    $parts[] = $key === 'template' ? $label : $c[$key] . ' ' . $label;
                }
            }

            $cards[] = [
                'id' => $p->id,
                'name' => $p->name,
                'sector' => $p->sector,
                'version' => $p->version,
                'validated' => $p->isValidated(),
                'validated_on' => $p->validated_at?->format('d/m/Y'),
                'description' => $p->description,
                'source' => $p->source,
                'contents' => implode(', ', $parts),
                'selected' => $p->id === $this->selectedId,
            ];
        }

        $selected = null;

        if ($this->selectedId) {
            $package = $this->offeredPackage($this->selectedId);

            if ($package) {
                $plan = PackageApplier::preview($package, $companyId);
                $selected = [
                    'id' => $package->id,
                    'name' => $package->name,
                    'version' => $package->version,
                    'validated' => $package->isValidated(),
                    'lines' => $this->describePlan($plan),
                    'totals' => $plan['totals'],
                    'missing' => $this->missingRights($package),
                ];
            }
        }

        $history = PayrollPackageApplication::where('company_id', $companyId)->orderByDesc('id')->limit(10)->get()->map(fn ($a) => [
            'when' => $a->created_at->format('d/m/Y H:i'),
            'name' => $a->package_name,
            'version' => $a->package_version,
            'validated' => $a->was_validated,
            'created' => (int) ($a->report['totals']['created'] ?? 0),
            'skipped' => (int) ($a->report['totals']['skipped'] ?? 0),
        ])->all();

        return view('livewire.payroll.packages', [
            'cards' => $cards,
            'selected' => $selected,
            'history' => $history,
            'canEdit' => $this->allowed('update', 'pay_items'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Démarrage rapide']);
    }
}
