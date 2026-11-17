<?php

namespace App\Livewire\Platform;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollPackage;
use App\Services\Payroll\PackageSchema;
use Database\Seeders\PayrollPackageSeeder;
use Livewire\Component;

/**
 * Espace plateforme : paquets par secteur (rubriques, modèles, grilles, modèle de bulletin) avec versions.
 * Comme les règles de paie : un paquet validé n'est jamais modifié ; on crée une nouvelle version, à valider à son tour.
 */
class PayrollPackages extends Component
{
    use PayrollGuards;

    /** null | new | edit */
    public ?string $formMode = null;
    public ?int $packageId = null;

    public string $code = '';
    public string $name = '';
    public string $sector = '';
    public string $description = '';
    public string $source = '';
    public string $payloadJson = '';

    /** @var array<int, string> */
    public array $payloadErrors = [];

    public string $flash = '';

    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
    }

    /** Droit « plans.manage » de la plateforme (même contrôle que les règles de paie). */
    protected function canManage(): bool
    {
        $user = auth()->user();

        return $user && $user->is_platform_admin && $user->platformCan('plans.manage');
    }

    public function seedStarters(): void
    {
        abort_unless($this->canManage(), 403);

        $created = PayrollPackageSeeder::seed(auth()->id());
        $this->audit('create', 'payroll_packages', null, null, ['starter_packages' => $created]);
        $this->flash = $created > 0
            ? $created . ' paquet(s) de départ créé(s), à valider par un comptable.'
            : 'Les paquets de départ existent déjà.';
    }

    public function newPackage(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->formMode = 'new';
        $this->payloadJson = "{\n  \"items\": [],\n  \"profiles\": []\n}";
    }

    public function editPackage(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $package = PayrollPackage::findOrFail($id);

        if ($package->isValidated()) {
            $this->flash = 'Un paquet validé ne se modifie pas : créez une nouvelle version.';

            return;
        }

        $this->resetForm();
        $this->formMode = 'edit';
        $this->packageId = $package->id;
        $this->code = $package->code;
        $this->name = $package->name;
        $this->sector = (string) $package->sector;
        $this->description = (string) $package->description;
        $this->source = (string) $package->source;
        $this->payloadJson = $package->payload ? json_encode($package->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : "{\n}";
    }

    public function newVersion(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $base = PayrollPackage::findOrFail($id);

        $latest = PayrollPackage::where('code', $base->code)->orderByDesc('version')->first();

        if ($latest && ! $latest->isValidated()) {
            $this->flash = 'La version ' . $latest->version . ' de ce paquet n\'est pas encore validée : modifiez-la ou validez-la avant d\'en créer une autre.';

            return;
        }

        $copy = PayrollPackage::create([
            'code' => $base->code, 'name' => $base->name, 'sector' => $base->sector, 'version' => ($latest?->version ?? $base->version) + 1,
            'status' => 'to_validate', 'description' => $base->description, 'source' => $base->source,
            'payload' => $base->payload ?? [], 'created_by' => auth()->id(),
        ]);

        $this->audit('create', 'payroll_packages', $copy->id, null, ['code' => $copy->code, 'version' => $copy->version]);
        $this->flash = 'Version ' . $copy->version . ' créée à partir de la version ' . $base->version . ' : modifiez-la puis faites-la valider.';
        $this->editPackage($copy->id);
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->resetErrorBag();
        $this->formMode = null;
        $this->packageId = null;
        $this->code = '';
        $this->name = '';
        $this->sector = '';
        $this->description = '';
        $this->source = '';
        $this->payloadJson = '';
        $this->payloadErrors = [];
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $this->code = strtolower(trim($this->code));

        $this->validate([
            'code' => $this->formMode === 'new' ? ['required', 'regex:/^[a-z0-9_-]{2,40}$/'] : ['nullable'],
            'name' => 'required|string|max:150',
            'sector' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
            'source' => 'nullable|string|max:255',
            'payloadJson' => 'required|string',
        ], [
            'code.regex' => 'Le code doit contenir 2 à 40 caractères : lettres minuscules, chiffres, « _ » ou « - ».',
        ], ['name' => 'nom', 'payloadJson' => 'contenu']);

        $payload = json_decode($this->payloadJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->payloadErrors = ['JSON invalide : ' . json_last_error_msg() . '.'];

            return;
        }

        $this->payloadErrors = PackageSchema::validate($payload);

        if ($this->payloadErrors !== []) {
            return;
        }

        $data = [
            'name' => trim($this->name),
            'sector' => trim($this->sector) ?: null,
            'description' => trim($this->description) ?: null,
            'source' => trim($this->source) ?: null,
            'payload' => $payload,
        ];

        if ($this->formMode === 'edit') {
            $package = PayrollPackage::findOrFail((int) $this->packageId);

            if ($package->isValidated()) {
                $this->flash = 'Un paquet validé ne se modifie pas : créez une nouvelle version.';
                $this->resetForm();

                return;
            }

            $package->update($data);
            $this->audit('update', 'payroll_packages', $package->id, null, ['code' => $package->code, 'version' => $package->version]);
            $this->flash = 'Paquet ' . $package->code . ' (version ' . $package->version . ') mis à jour : toujours à valider.';
        } else {
            if (PayrollPackage::where('code', $this->code)->exists()) {
                $this->addError('code', 'Ce code existe déjà : utilisez « Nouvelle version » depuis le paquet existant.');

                return;
            }

            $package = PayrollPackage::create($data + ['code' => $this->code, 'version' => 1, 'status' => 'to_validate', 'created_by' => auth()->id()]);
            $this->audit('create', 'payroll_packages', $package->id, null, ['code' => $package->code, 'version' => 1]);
            $this->flash = 'Paquet ' . $package->code . ' créé : à valider par un comptable.';
        }

        $this->resetForm();
    }

    public function validatePackage(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $package = PayrollPackage::findOrFail($id);

        if ($package->isValidated()) {
            return;
        }

        if (PackageSchema::isEmpty($package->payload)) {
            $this->flash = 'Ce paquet est une coquille vide : remplissez-le avant de le valider.';

            return;
        }

        $errors = PackageSchema::validate($package->payload);

        if ($errors !== []) {
            $this->flash = 'Ce paquet contient des erreurs (' . count($errors) . ') : ouvrez-le avec « Modifier » pour les voir et les corriger.';

            return;
        }

        $package->update(['status' => 'validated', 'validated_by' => auth()->id(), 'validated_at' => now()]);
        $this->audit('validate', 'payroll_packages', $package->id, ['status' => 'to_validate'], ['status' => 'validated', 'code' => $package->code, 'version' => $package->version]);
        $this->flash = 'Paquet ' . $package->code . ' (version ' . $package->version . ') validé.';
    }

    public function deletePackage(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $package = PayrollPackage::findOrFail($id);

        if ($package->isValidated()) {
            $this->flash = 'Un paquet validé ne se supprime pas.';

            return;
        }

        if ($package->applications()->exists()) {
            $this->flash = 'Ce paquet a déjà été appliqué par une entreprise : il ne peut pas être supprimé.';

            return;
        }

        $this->audit('delete', 'payroll_packages', $package->id, ['code' => $package->code, 'version' => $package->version], null);
        $package->delete();
        $this->flash = 'Version supprimée.';
    }

    /** Nom du valideur, sans supposer le nom exact des colonnes de users. */
    protected function validatorName(PayrollPackage $p): string
    {
        $user = $p->validator;

        if (! $user) {
            return '';
        }

        $attrs = $user->getAttributes();

        return (string) ($attrs['name'] ?? $attrs['full_name'] ?? '');
    }

    public function render()
    {
        $groups = [];
        $pending = 0;

        foreach (PayrollPackage::with('validator')->withCount('applications')->orderBy('code')->orderByDesc('version')->get() as $p) {
            $c = PackageSchema::counts($p->payload);
            $empty = PackageSchema::isEmpty($p->payload);

            $parts = [];

            foreach ([['items', 'rubrique(s)'], ['profiles', 'modèle(s)'], ['grids', 'grille(s)']] as [$key, $label]) {
                if ($c[$key] > 0) {
                    $parts[] = $c[$key] . ' ' . $label;
                }
            }

            if ($c['template'] > 0) {
                $parts[] = 'modèle de bulletin';
            }

            if (! $p->isValidated()) {
                $pending++;
            }

            $groups[$p->code][] = [
                'id' => $p->id,
                'name' => $p->name,
                'sector' => $p->sector,
                'version' => $p->version,
                'validated' => $p->isValidated(),
                'validated_info' => $p->isValidated() ? trim(($this->validatorName($p) ? 'par ' . $this->validatorName($p) . ' ' : '') . ($p->validated_at ? 'le ' . $p->validated_at->format('d/m/Y') : '')) : '',
                'empty' => $empty,
                'contents' => implode(', ', $parts),
                'source' => $p->source,
                'applications' => (int) $p->applications_count,
            ];
        }

        return view('livewire.platform.payroll-packages', [
            'groups' => $groups,
            'pending' => $pending,
        ])->layout($this->resolveLayout('platform'), ['title' => 'Paquets par secteur']);
    }
}
