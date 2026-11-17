<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalary;
use App\Models\LoanInstallment;
use App\Models\PayrollPeriod;
use App\Models\PayrollVariable;
use App\Models\Payslip;
use App\Models\PayslipDelivery;

/**
 * Lecture SEULE des faits du tableau de bord paie pour un mois (aucune écriture, aucun recalcul de bulletin).
 * Les résultats alimentent les calculs purs PayrollDashboardStats, PayrollCycle et PayrollAlerts.
 */
final class PayrollDashboardFacts
{
    /**
     * @return array{
     *   period: string, period_status: string, calculated_at: ?string, closed_at: ?string,
     *   sums: ?array<string, int>, previous: array{period: string, status: string, sums: ?array<string, int>},
     *   pending_checks: int, skipped: int, pending_variables: int,
     *   setup: array<string, mixed>, legal: array<string, mixed>, off_grid: int,
     *   active_loans: int, installments_count: int, installments_amount: int, delivery: ?array<string, int>
     * }
     */
    public static function load(int $companyId, string $period): array
    {
        $model = PayrollPeriod::where('company_id', $companyId)->where('period', $period)->first();
        $status = $model?->status ?? 'none';
        $calculated = in_array($status, ['calculated', 'closed'], true);

        $previousPeriod = LoanSchedule::addMonths($period, -1);
        $previousModel = PayrollPeriod::where('company_id', $companyId)->where('period', $previousPeriod)->first();

        $sums = $model && $calculated ? self::sums($companyId, $model->id) : null;
        $setup = SetupFacts::facts($companyId);

        $summary = is_array($model?->summary) ? $model->summary : [];
        $loanIds = EmployeeLoan::where('company_id', $companyId)->where('status', 'active')->select('id');
        $installments = LoanInstallment::whereIn('employee_loan_id', $loanIds)->where('due_month', $period)->where('status', 'scheduled');

        return [
            'period' => $period,
            'period_status' => $status,
            'calculated_at' => $model?->calculated_at?->format('d/m/Y H:i'),
            'closed_at' => $model?->closed_at?->format('d/m/Y H:i'),
            'sums' => $sums,
            'previous' => [
                'period' => $previousPeriod,
                'status' => $previousModel?->status ?? 'none',
                'sums' => $previousModel && in_array($previousModel->status, ['calculated', 'closed'], true) ? self::sums($companyId, $previousModel->id) : null,
            ],
            'pending_checks' => $model && $calculated
                ? Payslip::where('company_id', $companyId)->where('payroll_period_id', $model->id)->where('check_status', 'to_check')->whereNull('acknowledged_at')->count()
                : 0,
            'skipped' => $calculated ? count((array) ($summary['skipped'] ?? [])) : 0,
            'pending_variables' => PayrollVariable::where('company_id', $companyId)->where('period', $period)->where('status', 'proposed')->count(),
            'setup' => $setup,
            'legal' => SetupFacts::legal($companyId),
            'off_grid' => self::offGrid($companyId),
            'active_loans' => EmployeeLoan::where('company_id', $companyId)->where('status', 'active')->count(),
            'installments_count' => (clone $installments)->count(),
            'installments_amount' => (int) (clone $installments)->sum('amount'),
            'delivery' => $model && $status === 'closed' ? self::delivery($companyId, $model->id) : null,
        ];
    }

    /** Sommes des colonnes déjà agrégées des bulletins du mois ; null si le mois n'a aucun bulletin. */
    protected static function sums(int $companyId, int $periodId): ?array
    {
        $row = Payslip::where('company_id', $companyId)
            ->where('payroll_period_id', $periodId)
            ->toBase()
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(gross), 0) as gross, COALESCE(SUM(net_to_pay), 0) as net_to_pay, COALESCE(SUM(employee_contributions), 0) as employee_contributions, COALESCE(SUM(its), 0) as its, COALESCE(SUM(employer_total), 0) as employer_total, COALESCE(SUM(employer_cost), 0) as employer_cost')
            ->first();

        if (! $row || (int) $row->n === 0) {
            return null;
        }

        return [
            'count' => (int) $row->n,
            'gross' => (int) $row->gross,
            'net_to_pay' => (int) $row->net_to_pay,
            'employee_contributions' => (int) $row->employee_contributions,
            'its' => (int) $row->its,
            'employer_total' => (int) $row->employer_total,
            'employer_cost' => (int) $row->employer_cost,
        ];
    }

    /** Employés dont le salaire en vigueur est rattaché à un échelon, mais dont le montant de base diffère de celui de l'échelon. */
    protected static function offGrid(int $companyId): int
    {
        return (int) EmployeeSalary::where('employee_salaries.company_id', $companyId)
            ->inForceOn(now()->toDateString())
            ->whereNotNull('employee_salaries.salary_step_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'employee_salaries.salary_step_id')
            ->whereColumn('employee_salaries.base_amount', '<>', 'salary_steps.amount')
            ->distinct()
            ->count('employee_salaries.employee_id');
    }

    /** Résumé d'envoi des bulletins d'une paie clôturée (même règle que l'écran « Paie du mois »). Null s'il n'y a aucun bulletin. */
    protected static function delivery(int $companyId, int $periodId): ?array
    {
        $payslips = Payslip::where('company_id', $companyId)->where('payroll_period_id', $periodId)->get(['id', 'employee_id']);

        if ($payslips->isEmpty()) {
            return null;
        }

        $contacts = Employee::whereIn('id', $payslips->pluck('employee_id'))->get(['id', 'work_email', 'personal_email'])->keyBy('id');
        $rows = PayslipDelivery::whereIn('payslip_id', $payslips->pluck('id'))->get()->groupBy('payslip_id');
        $items = [];

        foreach ($payslips as $p) {
            $contact = $contacts->get($p->employee_id);
            $byChannel = $rows->get($p->id, collect())->keyBy('channel');
            $work = DeliveryState::channel($byChannel->has('work_email') ? self::row($byChannel['work_email']) : null, $contact?->work_email);
            $personal = DeliveryState::channel($byChannel->has('personal_email') ? self::row($byChannel['personal_email']) : null, $contact?->personal_email);
            $app = $byChannel->get('app');

            $items[] = [
                'overall' => DeliveryState::overall([$work, $personal]),
                'seen_app' => $app?->viewed_at !== null,
                'opened_email' => ($work['opened_at'] ?? $personal['opened_at'] ?? null) !== null,
            ];
        }

        return DeliveryState::summarize($items);
    }

    /** @return array{status: string, sent_at: ?string, error: ?string, opened_at: ?string} */
    protected static function row(PayslipDelivery $r): array
    {
        return ['status' => $r->status, 'sent_at' => $r->sent_at?->format('d/m/Y H:i'), 'error' => $r->error, 'opened_at' => $r->opened_at?->format('d/m/Y H:i')];
    }
}
