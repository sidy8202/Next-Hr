<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\PayrollSetting;
use App\Models\PayrollSetupState;
use App\Models\PayrollVariableSetting;
use App\Services\Payroll\PayrollSetupBadge;
use App\Services\Payroll\SetupFacts;
use App\Services\Payroll\SetupProgress;
use App\Services\Payroll\SetupSteps;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

/** Assistant « Démarrer la paie » : liste de contrôle calculée en direct, avec barre de progression. */
class SetupWizard extends Component
{
    use PayrollGuards;

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'payroll_settings');
    }

    public function welcomeWizard(): void
    {
        $this->allow('update', 'payroll_settings');

        PayrollSetupState::forCompany($this->companyId())->update(['welcomed_at' => now()]);
        PayrollSetupBadge::flush();
    }

    /** Confirme les valeurs affichées d'une étape (elles sont alors enregistrées telles quelles). */
    public function confirmStep(string $key): void
    {
        $this->allow('update', 'payroll_settings');
        abort_unless(in_array($key, PayrollSetupState::CONFIRMABLE, true), 404);

        $companyId = $this->companyId();

        // Les valeurs de départ n'existent parfois qu'à l'écran : on les enregistre pour qu'elles deviennent réelles.
        if ($key === 'settings') {
            PayrollSetting::forCompany($companyId);
        } else {
            PayrollVariableSetting::forCompany($companyId);
        }

        $state = PayrollSetupState::forCompany($companyId);
        $state->confirm($key);

        $this->audit('update', 'payroll_settings', $state->id, null, ['setup_confirmed' => $key]);
        PayrollSetupBadge::flush();
        $this->flash = 'Valeurs confirmées. Vous pouvez toujours les modifier depuis leur écran.';
    }

    public function skipStep(string $key): void
    {
        $this->allow('update', 'payroll_settings');
        $this->assertOptional($key);

        $state = PayrollSetupState::forCompany($this->companyId());
        $state->skip($key);

        $this->audit('update', 'payroll_settings', $state->id, null, ['setup_skipped' => $key]);
        PayrollSetupBadge::flush();
        $this->flash = 'Étape ignorée. Vous pouvez la reprendre à tout moment.';
    }

    public function unskipStep(string $key): void
    {
        $this->allow('update', 'payroll_settings');
        $this->assertOptional($key);

        PayrollSetupState::forCompany($this->companyId())->unskip($key);
        PayrollSetupBadge::flush();
        $this->flash = '';
    }

    public function hideWizard(): void
    {
        $this->allow('update', 'payroll_settings');

        PayrollSetupState::forCompany($this->companyId())->update(['is_hidden' => true]);
        PayrollSetupBadge::flush();
        $this->flash = 'Assistant masqué : la pastille du menu disparaît. Vous pouvez le rouvrir ici à tout moment.';
    }

    public function showWizard(): void
    {
        $this->allow('update', 'payroll_settings');

        PayrollSetupState::forCompany($this->companyId())->update(['is_hidden' => false]);
        PayrollSetupBadge::flush();
        $this->flash = '';
    }

    protected function assertOptional(string $key): void
    {
        $def = SetupSteps::byKey()[$key] ?? null;

        abort_unless($def !== null && ! $def['required'], 404);
    }

    public function render()
    {
        $companyId = $this->companyId();
        $canEdit = $this->allowed('update', 'payroll_settings');

        $state = PayrollSetupState::peek($companyId);
        $progress = SetupProgress::evaluate(SetupFacts::facts($companyId));
        $legal = $this->legalMessage(SetupFacts::legal($companyId));
        $defs = SetupSteps::byKey();

        $labels = [
            SetupProgress::DONE => 'Fait',
            SetupProgress::PARTIAL => 'En cours',
            SetupProgress::TODO => 'À faire',
            SetupProgress::OPTIONAL => 'Facultatif',
            SetupProgress::SKIPPED => 'Ignoré',
        ];

        $classes = [
            SetupProgress::DONE => 'rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700',
            SetupProgress::PARTIAL => 'rounded-full bg-sky-500/15 px-2 py-0.5 text-xs text-sky-700',
            SetupProgress::TODO => 'rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700',
            SetupProgress::OPTIONAL => 'rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted',
            SetupProgress::SKIPPED => 'rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted',
        ];

        $steps = [];

        foreach ($progress['steps'] as $index => $s) {
            $def = $defs[$s['key']];
            $status = $s['status'];

            $steps[] = [
                'key' => $s['key'],
                'number' => $index + 1,
                'title' => $s['title'],
                'required' => $s['required'],
                'status' => $status,
                'status_label' => $labels[$status],
                'status_class' => $classes[$status],
                'detail' => $s['detail'],
                'why' => $def['why'],
                'example' => $def['example'],
                'route_label' => $def['route_label'],
                'url' => Route::has($def['route']) && $this->allowed('read', $def['resource']) ? route($def['route']) . (isset($def['anchor']) ? '#' . $def['anchor'] : '') : null,
                'extra_url' => isset($def['extra_route']) && Route::has($def['extra_route']) && $this->allowed('read', $def['extra_resource']) ? route($def['extra_route']) . (isset($def['extra_anchor']) ? '#' . $def['extra_anchor'] : '') : null,
                'extra_label' => $def['extra_label'] ?? '',
                'values' => $this->valuesFor($s['key']),
                'is_open' => $s['key'] === $progress['next'],
                'can_confirm' => $def['confirmable'] && $canEdit && $status !== SetupProgress::DONE,
                'can_skip' => ! $s['required'] && $canEdit && in_array($status, [SetupProgress::OPTIONAL], true),
                'can_unskip' => ! $s['required'] && $canEdit && $status === SetupProgress::SKIPPED,
            ];
        }

        return view('livewire.payroll.setup-wizard', [
            'steps' => $steps,
            'percent' => $progress['percent'],
            'requiredDone' => $progress['required_done'],
            'requiredTotal' => $progress['required_total'],
            'complete' => $progress['complete'],
            'legal' => $legal,
            'hidden' => (bool) ($state && $state->is_hidden),
            'welcomed' => (bool) ($state && $state->welcomed_at),
            'canEdit' => $canEdit,
        ])->layout($this->resolveLayout('company'), ['title' => 'Démarrer la paie']);
    }

    /**
     * Message sur les règles légales de la plateforme.
     *
     * @param array{state: string, total: int, validated: int, last_validated_at: ?string} $legal
     *
     * @return array{level: string, text: string}
     */
    protected function legalMessage(array $legal): array
    {
        if ($legal['state'] === 'missing') {
            return [
                'level' => 'warn',
                'text' => "Les règles légales (taux INPS, AMO, barème ITS) ne sont pas chargées pour votre pays : aucun bulletin ne peut être calculé tant que la plateforme ne les a pas chargées. Contactez l'administrateur de la plateforme.",
            ];
        }

        if ($legal['state'] === 'to_validate') {
            $todo = $legal['total'] - $legal['validated'];

            return [
                'level' => 'warn',
                'text' => "$todo règle(s) légale(s) sur {$legal['total']} ne sont pas encore validées par un comptable. Vous pouvez préparer la paie, mais il est prudent d'attendre leur validation avant de clôturer une paie réelle.",
            ];
        }

        $date = $legal['last_validated_at'] ? \Carbon\Carbon::parse($legal['last_validated_at'])->format('d/m/Y') : null;

        return [
            'level' => 'ok',
            'text' => 'Règles légales validées par un comptable' . ($date ? " (dernière validation le $date)." : '.'),
        ];
    }

    /**
     * Valeurs actuelles montrées dans les étapes à confirmer (valeurs de départ si la ligne n'existe pas encore).
     *
     * @return array<int, array{label: string, value: string}>
     */
    protected function valuesFor(string $key): array
    {
        $companyId = $this->companyId();

        if ($key === 'settings') {
            $row = PayrollSetting::where('company_id', $companyId)->first();
            $attrs = $row ? $row->getAttributes() : [];
            $rounding = ['nearest' => 'Au franc le plus proche', 'floor' => 'Au franc inférieur', 'ceil' => 'Au franc supérieur'];

            return [
                ['label' => 'Pays', 'value' => ($attrs['country'] ?? 'ML') === 'ML' ? 'Mali' : (string) $attrs['country']],
                ['label' => 'Convention collective', 'value' => trim((string) ($attrs['convention_name'] ?? '')) ?: 'Non renseignée'],
                ['label' => 'Base de jours d\'un mois', 'value' => (string) ($attrs['days_basis'] ?? 30)],
                ['label' => 'Heures du mois', 'value' => str_replace('.', ',', rtrim(rtrim(number_format((float) ($attrs['hours_month'] ?? 173.33), 2, '.', ''), '0'), '.'))],
                ['label' => 'Arrondi', 'value' => $rounding[$attrs['rounding'] ?? 'nearest'] ?? 'Au franc le plus proche'],
                ['label' => 'Part employeur sur le bulletin', 'value' => ! empty($attrs['show_employer_part']) ? 'Affichée' : 'Masquée'],
            ];
        }

        if ($key === 'variable_settings') {
            $row = PayrollVariableSetting::where('company_id', $companyId)->first();
            $attrs = $row ? $row->getAttributes() : [];
            $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            $rest = $row ? $row->restDays() : [0];

            $arrival = (string) ($attrs['arrival_rule'] ?? 'threshold');
            $arrivalDays = (int) ($attrs['arrival_grace_days'] ?? 5);
            $departure = (string) ($attrs['departure_rule'] ?? 'prorate');
            $departureDays = (int) ($attrs['departure_grace_days'] ?? 5);

            $arrivalText = [
                'prorate' => 'Toujours retirer les jours avant l\'arrivée',
                'full' => 'Toujours le mois entier',
                'threshold' => "Mois entier si l'arrivée a lieu au plus tard le $arrivalDays",
            ][$arrival] ?? 'Mois entier si l\'arrivée a lieu au plus tard le 5';

            $departureText = [
                'prorate' => 'Retirer les jours après le départ',
                'full' => 'Toujours le mois entier',
                'threshold' => "Mois entier si le départ tombe dans les $departureDays derniers jours",
            ][$departure] ?? 'Retirer les jours après le départ';

            return [
                ['label' => 'Arrivée en cours de mois', 'value' => $arrivalText],
                ['label' => 'Départ en cours de mois', 'value' => $departureText],
                ['label' => 'Jours de repos', 'value' => implode(', ', array_map(fn ($d) => $days[$d] ?? (string) $d, $rest))],
                ['label' => 'Tolérance du contrôle des nets', 'value' => number_format((int) ($attrs['net_tolerance'] ?? 100), 0, ',', ' ') . ' FCFA'],
            ];
        }

        return [];
    }
}
