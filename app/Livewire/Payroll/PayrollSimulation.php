<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\PayProfile;
use App\Models\Payslip;
use App\Models\SalaryStep;
use App\Services\Payroll\HypotheticalPay;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayrollInputBuilder;
use App\Services\Payroll\ScenarioComparison;
use App\Services\Payroll\ScenarioInput;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Simulation de paie : calcule et explique, n'enregistre rien.
 * Deux modes : « Employé existant » (paie d'un employé pour un mois, ou bulletin enregistré) et
 * « Personne hypothétique » (jusqu'à trois scénarios côte à côte, pour une embauche ou une augmentation à l'étude).
 * Chaque scénario part du salaire de base, du brut voulu ou du net voulu : dans les deux derniers cas, le salaire de base est cherché.
 */
class PayrollSimulation extends Component
{
    use PayrollGuards;

    /** employee | hypothetical */
    #[Url(as: 'mode')]
    public string $mode = 'employee';

    #[Url]
    public $employeeId = '';
    #[Url]
    public string $period = '';
    /** Numéro d'un bulletin enregistré : on affiche alors le bulletin figé, sans le recalculer. */
    #[Url]
    public $payslip = '';
    public string $referenceNet = '';
    public string $infirmAdults = '0';

    /** Scénarios du mode « personne hypothétique » (valeurs brutes de l'écran, nettoyées par ScenarioInput). */
    public array $scenarios = [];

    public function mount(): void
    {
        $this->allow('read', 'payroll_simulation');
        if ($this->period === '') {
            $this->period = now()->format('Y-m');
        }

        if (! in_array($this->mode, ['employee', 'hypothetical'], true)) {
            $this->mode = 'employee';
        }

        $this->scenarios = [ScenarioInput::blank(ScenarioInput::defaultLabel(0)), ScenarioInput::blank(ScenarioInput::defaultLabel(1))];
    }

    public function updatedPeriod(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->period)) {
            $this->period = now()->format('Y-m');
        }
    }

    public function setMode(string $mode): void
    {
        $this->allow('read', 'payroll_simulation');

        if (in_array($mode, ['employee', 'hypothetical'], true)) {
            $this->mode = $mode;
        }
    }

    public function addScenario(): void
    {
        $this->allow('read', 'payroll_simulation');

        if (count($this->scenarios) < ScenarioInput::MAX_SCENARIOS) {
            $this->scenarios[] = ScenarioInput::blank(ScenarioInput::defaultLabel(count($this->scenarios)));
        }
    }

    /** Copie un scénario pour en essayer une variante (autre échelon, autre famille, autre net). */
    public function duplicateScenario(int $index): void
    {
        $this->allow('read', 'payroll_simulation');

        if (isset($this->scenarios[$index]) && count($this->scenarios) < ScenarioInput::MAX_SCENARIOS) {
            $copy = $this->scenarios[$index];
            $copy['label'] = mb_substr(trim((string) ($copy['label'] ?? '')) . ' (copie)', 0, 40);
            $this->scenarios[] = $copy;
        }
    }

    public function removeScenario(int $index): void
    {
        $this->allow('read', 'payroll_simulation');

        if (count($this->scenarios) > 1 && isset($this->scenarios[$index])) {
            unset($this->scenarios[$index]);
            $this->scenarios = array_values($this->scenarios);
        }
    }

    /**
     * Choisir un échelon remplit le montant (il n'existe que pour le point de départ « salaire de base ») ;
     * taper un montant à la main détache l'échelon ; changer de point de départ efface l'échelon et le net visé.
     */
    public function updatedScenarios($value, $key): void
    {
        $this->allow('read', 'payroll_simulation');

        if (! preg_match('/^(\d+)\.(step_id|amount|from)$/', (string) $key, $m) || ! isset($this->scenarios[(int) $m[1]])) {
            return;
        }

        $i = (int) $m[1];

        if ($m[2] === 'amount') {
            $this->scenarios[$i]['step_id'] = '';

            return;
        }

        if ($m[2] === 'from') {
            $this->scenarios[$i]['step_id'] = '';

            if ($value !== 'base') {
                $this->scenarios[$i]['net'] = '';
            }

            return;
        }

        if ($this->canSeeGrids() && ctype_digit((string) $value)) {
            $step = SalaryStep::whereHas('grade.grid')->find((int) $value);

            if ($step && ($this->scenarios[$i]['from'] ?? 'base') === 'base') {
                $this->scenarios[$i]['amount'] = (string) (int) $step->amount;

                return;
            }
        }

        $this->scenarios[$i]['step_id'] = '';
    }

    /** Les échelons de la grille montrent des salaires : réservés à ceux qui peuvent lire les grilles. */
    protected function canSeeGrids(): bool
    {
        return auth()->user()->canDo('read', 'salary_grids');
    }

    protected function canSeeProfiles(): bool
    {
        return auth()->user()->canDo('read', 'pay_profiles');
    }

    /** Garde la trace de la première simulation de contrôle pour l'assistant « Démarrer la paie ». Ne bloque jamais l'écran. */
    protected function recordSimulation(): void
    {
        try {
            \App\Models\PayrollSetupState::markSimulationDone($this->companyId());
        } catch (\Throwable) {
            // l'assistant ne doit jamais empêcher de simuler (table pas encore migrée, par exemple)
        }
    }

    /**
     * Calcule chaque scénario. Une erreur d'un scénario (règles légales absentes, par exemple) n'empêche pas les autres.
     *
     * @param array<int, int> $allowedProfiles identifiants de modèles autorisés : tout autre identifiant est ignoré
     *
     * @return array<int, array{label: string, summary: ?array, error: ?string}>
     */
    protected function runScenarios(array $allowedProfiles): array
    {
        $results = [];

        foreach (array_values($this->scenarios) as $i => $raw) {
            $n = ScenarioInput::normalize((array) $raw, ScenarioInput::defaultLabel($i));
            $problem = ScenarioInput::problem($n);

            if ($problem !== null) {
                $results[] = ['label' => $n['label'], 'summary' => null, 'error' => $problem];

                continue;
            }

            $profile = $n['pay_profile_id'] !== null && in_array($n['pay_profile_id'], $allowedProfiles, true) ? $n['pay_profile_id'] : null;

            try {
                $summary = HypotheticalPay::preview($this->companyId(), [
                    'from' => $n['from'],
                    'base' => $n['from'] === 'base' ? $n['amount'] : 0,
                    'target' => $n['from'] === 'base' ? 0 : $n['amount'],
                    'marital_status' => $n['marital_status'],
                    'children' => $n['children'],
                    'infirm_adults' => $n['infirm_adults'],
                    'seniority_years' => $n['seniority_years'],
                    'period' => $this->period,
                    'pay_profile_id' => $profile,
                    'guaranteed_net' => $n['net'],
                ]);

                $results[] = ['label' => $n['label'], 'summary' => $summary, 'error' => null];
            } catch (\RuntimeException $e) {
                $results[] = ['label' => $n['label'], 'summary' => null, 'error' => $e->getMessage()];
            } catch (\Throwable $e) {
                report($e);
                $results[] = ['label' => $n['label'], 'summary' => null, 'error' => "Ce scénario n'a pas pu être calculé."];
            }
        }

        return $results;
    }

    public function render()
    {
        $employees = Employee::orderBy('matricule')->get();

        $sim = null;
        $meta = null;
        $its = null;
        $error = null;
        $gap = null;
        $frozen = null;

        if ($this->mode === 'employee' && $this->payslip !== '' && $this->payslip !== null) {
            $saved = Payslip::find((int) $this->payslip);

            if (! $saved) {
                $error = 'Bulletin introuvable.';
            } else {
                $snapshot = $saved->snapshot;
                $sim = $snapshot['result'];
                $meta = $snapshot['meta'];
                $meta['notes'] = [];
                $its = PayrollCalculator::explainIts($snapshot['rules'], $sim['totals']['its_base'], $snapshot['family']);
                $reference = $this->digits($this->referenceNet);
                $gap = $reference > 0 ? $sim['totals']['net_to_pay'] - $reference : null;
                $frozen = ($saved->status === 'final' ? 'Bulletin clôturé' : 'Bulletin provisoire') . ' de ' . substr($saved->period, 5, 2) . '/' . substr($saved->period, 0, 4)
                    . ' : les chiffres ci-dessous sont ceux enregistrés au calcul du ' . ($snapshot['calculated_at'] ? \Carbon\Carbon::parse($snapshot['calculated_at'])->format('d/m/Y à H:i') : '—')
                    . ', ils ne changent pas si les données évoluent ensuite.';
            }
        } elseif ($this->mode === 'employee' && $this->employeeId !== '' && $this->employeeId !== null) {
            try {
                $built = PayrollInputBuilder::build((int) $this->employeeId, $this->period, ['infirm_adults' => min(10, $this->digits($this->infirmAdults))]);
                $meta = $built['meta'];
                $sim = PayrollCalculator::compute($built['input']);
                $this->recordSimulation();
                $its = PayrollCalculator::explainIts($built['input']['rules'], $sim['totals']['its_base'], $built['input']['family']);

                $reference = $this->digits($this->referenceNet);
                $gap = $reference > 0 ? $sim['totals']['net_to_pay'] - $reference : null;
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        // Mode « personne hypothétique » : échelons et modèles proposés selon les droits, puis calcul des scénarios.
        $stepGroups = [];
        $profiles = [];
        $results = [];
        $table = null;
        $notes = [];

        if ($this->mode === 'hypothetical') {
            if ($this->canSeeGrids()) {
                $steps = SalaryStep::whereHas('grade.grid')->with(['grade:id,salary_grid_id,code,label', 'grade.grid:id,name'])->orderBy('salary_grade_id')->orderBy('sort')->orderBy('id')->get(['id', 'salary_grade_id', 'label', 'amount']);

                foreach ($steps->groupBy('salary_grade_id') as $group) {
                    $grade = $group->first()->grade;
                    $stepGroups[] = [
                        'label' => ($grade?->grid?->name ? $grade->grid->name . ' · ' : '') . $grade?->code . ' ' . $grade?->label,
                        'steps' => $group->map(fn ($s) => ['id' => $s->id, 'label' => $s->label, 'amount' => (int) $s->amount])->all(),
                    ];
                }
            }

            if ($this->canSeeProfiles()) {
                $profiles = PayProfile::where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name])->all();
            }

            $results = $this->runScenarios(array_column($profiles, 'id'));
            $table = ScenarioComparison::table($results);
            $notes = ScenarioComparison::solveNotes($results);
        }

        return view('livewire.payroll.payroll-simulation', [
            'employees' => $employees->map(fn ($e) => ['id' => $e->id, 'label' => ($e->matricule ? $e->matricule . ' · ' : '') . $this->employeeName($e)]),
            'sim' => $sim,
            'meta' => $meta,
            'its' => $its,
            'error' => $error,
            'gap' => $gap,
            'frozen' => $frozen,
            'stepGroups' => $stepGroups,
            'profiles' => $profiles,
            'results' => $results,
            'table' => $table,
            'notes' => $notes,
            'fromOptions' => ScenarioInput::FROM,
            'maxScenarios' => ScenarioInput::MAX_SCENARIOS,
        ])->layout($this->resolveLayout('company'), ['title' => 'Simulation de paie']);
    }
}
