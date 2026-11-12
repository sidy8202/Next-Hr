<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LoanInstallment;
use App\Models\PayItem;
use App\Models\PayrollSetting;
use App\Models\PayrollVariable;
use App\Models\PayrollVariableSetting;

/**
 * Lignes variables d'un employé pour un mois : variables CONFIRMÉES + échéances de prêts à prélever.
 * Le moteur de calcul (lot 13d) lira ce service. Les montants sont calculés ici à partir de la quantité
 * et du salaire en vigueur en fin de mois, sauf si le RH a imposé un montant.
 */
class PayVariables
{
    /** Rubrique système rattachée à chaque nature de variable. */
    public const KIND_ITEM_KEY = [
        'overtime_hours' => 'overtime',
        'worked_days' => 'worked_days',
        'unpaid_absence' => 'absence_deduction',
        'suspension' => 'suspension',
        'assignment' => 'assignment_allowance',
    ];

    /** @return array{pay: array<int, float>, daysBasis: int, hoursMonth: float, rounding: string} */
    public static function context(int $companyId, array $employeeIds, string $period): array
    {
        $payrollSetting = PayrollSetting::forCompany($companyId);
        $variableSetting = PayrollVariableSetting::forCompany($companyId);
        [, $end] = PayrollDays::monthBounds($period);

        $pay = [];

        foreach (EmployeeSalary::whereIn('employee_id', $employeeIds)->inForceOn($end)->orderBy('effective_from')->get() as $salary) {
            $pay[(int) $salary->employee_id] = (float) $salary->base_amount + ($variableSetting->pay_basis === 'base_supplement' ? (float) $salary->supplement_amount : 0.0);
        }

        return [
            'pay' => $pay,
            'daysBasis' => (int) $payrollSetting->days_basis,
            'hoursMonth' => (float) $payrollSetting->hours_month,
            'rounding' => (string) $payrollSetting->rounding,
        ];
    }

    /** Montant d'une variable (positif), ou null s'il n'est pas calculable (salaire introuvable, montant à saisir). */
    public static function amountFor(PayrollVariable $v, array $ctx): ?int
    {
        if ($v->amount_override !== null) {
            return (int) $v->amount_override;
        }

        $pay = $ctx['pay'][(int) $v->employee_id] ?? null;

        if ($pay === null) {
            return null;
        }

        $qty = (float) $v->quantity;
        $majoration = (float) ($v->rate_percent ?? 0);

        return match ($v->kind) {
            'unpaid_absence', 'suspension' => PayrollDays::deduction($pay, $ctx['daysBasis'], $qty, $ctx['rounding']),
            'worked_days' => PayrollDays::workedDaysAmount($pay, $ctx['daysBasis'], $qty, $majoration, $ctx['rounding']),
            'overtime_hours' => PayrollDays::overtimeAmount($pay, $ctx['hoursMonth'], $qty, $majoration, $ctx['rounding']),
            default => null,
        };
    }

    /** gross_add (s'ajoute au brut) | gross_deduct (réduit le brut) | net_deduct (retenue sur le net) */
    public static function effectOfType(string $itemType): string
    {
        return match ($itemType) {
            'gross_deduction' => 'gross_deduct',
            'deduction_internal', 'deduction_individual' => 'net_deduct',
            default => 'gross_add',
        };
    }

    public static function effectOf(PayrollVariable $v): string
    {
        return match ($v->kind) {
            'unpaid_absence', 'suspension' => 'gross_deduct',
            'overtime_hours', 'worked_days', 'assignment' => 'gross_add',
            default => $v->item ? self::effectOfType($v->item->type) : 'gross_add',
        };
    }

    /**
     * @return array{lines: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public static function forEmployee(int $employeeId, string $period): array
    {
        $companyId = (int) Employee::whereKey($employeeId)->value('company_id');
        $ctx = self::context($companyId, [$employeeId], $period);
        $items = PayItem::whereNotNull('system_key')->get()->keyBy('system_key');

        $lines = [];
        $warnings = [];

        foreach (PayrollVariable::with('item')->where('employee_id', $employeeId)->where('period', $period)->where('status', 'confirmed')->orderBy('id')->get() as $v) {
            $item = $v->item ?? ($items[self::KIND_ITEM_KEY[$v->kind] ?? ''] ?? null);
            $amount = self::amountFor($v, $ctx);
            $label = $item?->label ?? (PayrollVariable::KINDS[$v->kind] ?? $v->kind);

            if ($amount === null) {
                $warnings[] = "« $label » : montant non calculable (salaire introuvable ou montant à saisir).";
            }

            $lines[] = [
                'source' => 'variable', 'id' => $v->id, 'kind' => $v->kind, 'item_id' => $item?->id, 'code' => $item?->code, 'label' => $label,
                'effect' => self::effectOf($v), 'quantity' => $v->quantity, 'unit' => $v->unit, 'amount' => $amount, 'note' => $v->source_ref ?? $v->note,
            ];
        }

        $installments = LoanInstallment::with('loan')->where('due_month', $period)->where('status', 'scheduled')
            ->whereHas('loan', fn ($q) => $q->where('employee_id', $employeeId)->where('status', 'active'))->get();

        foreach ($installments as $i) {
            $item = $items[$i->loan->type] ?? null;

            $lines[] = [
                'source' => 'loan', 'id' => $i->id, 'kind' => 'loan_installment', 'item_id' => $item?->id, 'code' => $item?->code,
                'label' => $item?->label ?? ($i->loan->type === 'advance' ? "Retenue d'avance sur salaire" : 'Remboursement de prêt'),
                'effect' => 'net_deduct', 'quantity' => null, 'unit' => null, 'amount' => (int) $i->amount, 'note' => $i->loan->label,
            ];
        }

        return ['lines' => $lines, 'warnings' => $warnings];
    }
}
