<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\PayItem;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\PayrollVariableSetting;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prépare les données d'une paie (employé + mois) à partir de tout ce que l'application sait déjà :
 * salaire en vigueur, règles légales datées, modèle de traitement et primes attribuées (lot 13b),
 * variables confirmées et échéances de prêts (lot 13c), situation de famille et ancienneté de l'employé.
 * Le résultat se donne tel quel à PayrollCalculator::compute().
 */
final class PayrollInputBuilder
{
    /**
     * @param array{
     *     infirm_adults?: int,
     *     salary?: array{base: int, supplement: int, mode: string, target_net: ?int},
     *     without_variables?: bool,
     *     full_month?: bool
     * } $overrides  salary : salaire à essayer sans l'enregistrer ; without_variables et full_month : net « du contrat »
     * @return array{input: array<string, mixed>, meta: array<string, mixed>}
     *
     * @throws \RuntimeException si une donnée indispensable manque (salaire, règles légales)
     */
    public static function build(int $employeeId, string $period, array $overrides = []): array
    {
        $employee = Employee::findOrFail($employeeId);
        $attrs = $employee->getAttributes();
        $companyId = (int) ($attrs['company_id'] ?? 0);
        [$start, $end] = PayrollDays::monthBounds($period);
        $warnings = [];

        // 1. Salaire en vigueur à la fin du mois (ou salaire à essayer).
        $salaryFrom = null;
        $gradeCode = null;
        $stepLabel = null;

        if (isset($overrides['salary'])) {
            $salaryData = [
                'base' => (int) $overrides['salary']['base'], 'supplement' => (int) ($overrides['salary']['supplement'] ?? 0),
                'mode' => (string) ($overrides['salary']['mode'] ?? 'gross'), 'target_net' => $overrides['salary']['target_net'] ?? null,
            ];
        } else {
            $salary = EmployeeSalary::where('employee_id', $employeeId)->inForceOn($end)->orderByDesc('effective_from')->first();

            if (! $salary) {
                $salary = EmployeeSalary::where('employee_id', $employeeId)->whereDate('effective_from', '<=', $end)->orderByDesc('effective_from')->first();

                if (! $salary) {
                    throw new \RuntimeException('Aucun salaire enregistré pour cet employé à cette date : saisissez-le dans « Salaires des employés ».');
                }

                $warnings[] = 'Le dernier salaire enregistré est terminé avant la fin du mois : il est utilisé tel quel.';
            }

            $changes = EmployeeSalary::where('employee_id', $employeeId)->whereDate('effective_from', '>', $start)->whereDate('effective_from', '<=', $end)->count();

            if ($changes > 0) {
                $warnings[] = 'Le salaire (ou le net connu) change en cours de mois : le nouveau salaire est appliqué au mois entier. Vérifiez si un prorata est nécessaire.';
            }

            $salaryData = [
                'base' => (int) $salary->base_amount, 'supplement' => (int) $salary->supplement_amount,
                'mode' => (string) $salary->mode, 'target_net' => $salary->target_net !== null ? (int) $salary->target_net : null,
            ];
            $salaryFrom = $salary->effective_from->toDateString();
            $salary->loadMissing(['grade', 'step']);
            $gradeCode = $salary->grade?->code;
            $stepLabel = $salary->step?->label;
        }

        // 2. Règles légales en vigueur.
        $rules = PayrollRule::activeOn($end);

        if ($rules === []) {
            throw new \RuntimeException('Aucune règle légale en vigueur à cette date : la plateforme doit charger les règles de paie.');
        }

        $unvalidated = array_values(array_map(fn ($r) => $r['label'], array_filter($rules, fn ($r) => ($r['status'] ?? '') !== 'validated')));

        if ($unvalidated !== []) {
            $warnings[] = count($unvalidated) . ' règle(s) légale(s) pas encore validée(s) par un comptable : ' . implode(', ', $unvalidated) . '.';
        }

        // 3. Réglages, rubriques et variables.
        $settings = PayrollSetting::forCompany($companyId);
        $lines = PayComposition::forEmployee($employeeId, $end);
        $variables = ! empty($overrides['without_variables']) ? [] : PayVariables::forEmployee($employeeId, $period)['lines'];

        $loanInstallmentIds = array_values(array_map(fn ($v) => (int) $v['id'], array_filter($variables, fn ($v) => ($v['source'] ?? '') === 'loan')));

        $items = PayItem::whereIn('id', array_filter(array_column($variables, 'item_id')))->get()->keyBy('id');

        foreach ($variables as &$v) {
            $item = $items[$v['item_id'] ?? 0] ?? null;
            $v['subject_to_social'] = $item ? (bool) $item->subject_to_social : true;
            $v['taxable'] = $item ? (bool) $item->taxable : true;
            $v['item_type'] = $item?->type;
        }
        unset($v);

        $labels = [];

        foreach (PayItem::whereIn('system_key', ['base_salary', 'supplement'])->get() as $item) {
            $labels[$item->system_key] = [
                'code' => $item->code, 'label' => $item->label, 'item_id' => $item->id,
                'social' => (bool) $item->subject_to_social, 'taxable' => (bool) $item->taxable,
            ];
        }

        // 4. Présence dans le mois et ancienneté.
        $hire = ! empty($attrs['hire_date']) ? Carbon::parse($attrs['hire_date'])->toDateString() : null;
        $exit = self::exitDate($employeeId);
        $factor = ! empty($overrides['full_month']) ? 1.0 : Proration::presenceFactor($hire, $exit, $start, $end, (int) $settings->days_basis, self::policy($companyId));
        $years = $hire ? max(0, (int) floor(Carbon::parse($hire)->diffInYears(Carbon::parse($end)))) : 0;

        if (empty($overrides['full_month'])) {
            if ($hire === null) {
                $warnings[] = "La date d'embauche est absente : ni prorata d'arrivée, ni ancienneté.";
            }

            if ($factor <= 0.0) {
                $warnings[] = 'L\'employé n\'est pas présent ce mois-ci (avant son embauche ou après son départ).';
            } elseif ($factor < 1.0) {
                $warnings[] = 'Mois incomplet (entrée ou sortie en cours de mois) : le salaire et les rubriques fixes sont proratisés.';
            } else {
                if ($hire && $hire > $start && $hire <= $end) {
                    $warnings[] = 'Arrivée le ' . Carbon::parse($hire)->format('d/m') . ' : mois payé en entier (règle de l\'entreprise pour les arrivées en cours de mois).';
                }

                if ($exit && $exit >= $start && $exit < $end) {
                    $warnings[] = 'Départ le ' . Carbon::parse($exit)->format('d/m') . ' : mois payé en entier (règle de l\'entreprise pour les départs en cours de mois).';
                }
            }
        }

        $family = [
            'marital_status' => $attrs['marital_status'] ?? null,
            'children' => (int) ($attrs['children_count'] ?? 0),
            'infirm_adults' => max(0, (int) ($overrides['infirm_adults'] ?? 0)),
        ];

        $input = [
            'rules' => $rules,
            'rounding' => (string) $settings->rounding,
            'factor' => $factor,
            'seniority_years' => $years,
            'salary' => $salaryData,
            'labels' => $labels,
            'items' => $lines,
            'variables' => $variables,
            'family' => $family,
        ];

        $name = trim(($attrs['first_name'] ?? '') . ' ' . ($attrs['last_name'] ?? ''));

        return [
            'input' => $input,
            'meta' => [
                'employee' => $name !== '' ? $name : ('#' . $employeeId),
                'matricule' => $attrs['matricule'] ?? null,
                'department_id' => isset($attrs['department_id']) ? (int) $attrs['department_id'] : null,
                'hire_date' => $hire,
                'exit_date' => $exit,
                'seniority_years' => $years,
                'factor' => $factor,
                'family' => $family,
                'salary_from' => $salaryFrom,
                'position' => Position::whereKey((int) ($attrs['position_id'] ?? 0))->value('title'),
                'grade' => $gradeCode,
                'step' => $stepLabel,
                'loan_installment_ids' => $loanInstallmentIds,
                'warnings' => $warnings,
                'notes' => [
                    'La situation de famille actuelle de la fiche employé est utilisée (l\'ITS retient en principe la situation au 1er janvier).',
                    'Les enfants majeurs infirmes ne sont pas enregistrés sur la fiche employé : indiquez-les ci-dessus si nécessaire.',
                ],
            ],
        ];
    }

    /** Facteur de présence d'un employé sur un mois (0 = absent tout le mois), sans calculer le reste. */
    public static function presence(int $employeeId, string $period): float
    {
        $employee = Employee::findOrFail($employeeId);
        $attrs = $employee->getAttributes();
        [$start, $end] = PayrollDays::monthBounds($period);
        $settings = PayrollSetting::forCompany((int) ($attrs['company_id'] ?? 0));
        $hire = ! empty($attrs['hire_date']) ? Carbon::parse($attrs['hire_date'])->toDateString() : null;

        return Proration::presenceFactor($hire, self::exitDate($employeeId), $start, $end, (int) $settings->days_basis, self::policy((int) ($attrs['company_id'] ?? 0)));
    }

    /**
     * Politique de l'entreprise pour une arrivée ou un départ en cours de mois (réglages des variables).
     *
     * @return array{arrival: array{rule: string, days: int}, departure: array{rule: string, days: int}}
     */
    public static function policy(int $companyId): array
    {
        $s = PayrollVariableSetting::forCompany($companyId);

        return [
            'arrival' => ['rule' => (string) ($s->arrival_rule ?? 'prorate'), 'days' => (int) ($s->arrival_grace_days ?? 0)],
            'departure' => ['rule' => (string) ($s->departure_rule ?? 'prorate'), 'days' => (int) ($s->departure_grace_days ?? 0)],
        ];
    }

    /** Dernier jour de présence, lu dans les départs enregistrés (table « terminations ») s'il existe. */
    protected static function exitDate(int $employeeId): ?string
    {
        try {
            if (! Schema::hasTable('terminations') || ! Schema::hasColumn('terminations', 'last_day')) {
                return null;
            }

            $date = DB::table('terminations')->where('employee_id', $employeeId)->value('last_day');

            return $date ? Carbon::parse($date)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
