<?php

namespace App\Services\Payroll;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LoanInstallment;
use App\Models\PayItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollVariable;
use App\Models\PayrollVariableSetting;
use App\Models\Payslip;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Cycle mensuel de la paie : calcul de tous les bulletins, clôture, régularisation.
 *  - calculer : un bulletin PROVISOIRE par employé, avec son contrôle de net ; recalculer remplace les provisoires ;
 *  - clôturer : fige les bulletins, marque les échéances de prêts comme prélevées, verrouille le mois ;
 *  - régulariser : une erreur sur une paie clôturée se corrige par une ligne tracée sur le mois suivant, jamais en rouvrant.
 */
final class PayrollRunner
{
    public static function period(string $period, int $companyId): PayrollPeriod
    {
        $p = PayrollPeriod::where('period', $period)->first();

        if (! $p) {
            $p = new PayrollPeriod(['period' => $period, 'status' => 'draft']);
            $p->company_id = $companyId;
            $p->save();
        }

        return $p;
    }

    /**
     * @return array{processed: int, skipped: array<int, array{employee: string, matricule: ?string, reason: string}>, inactive: int}
     */
    public static function calculate(PayrollPeriod $period, ?int $userId): array
    {
        if ($period->isClosed()) {
            throw new \RuntimeException('Cette paie est clôturée : elle ne se recalcule plus.');
        }

        $companyId = (int) $period->company_id;
        $tolerance = (int) PayrollVariableSetting::forCompany($companyId)->net_tolerance;
        $departments = Department::all()->mapWithKeys(fn ($d) => [$d->id => self::labelOf($d)])->all();
        $existing = Payslip::where('payroll_period_id', $period->id)->get()->keyBy('employee_id');

        $kept = [];
        $skipped = [];
        $rows = [];
        $warningCounts = [];
        $inactive = 0;

        DB::transaction(function () use ($period, $companyId, $tolerance, $departments, $existing, $userId, &$kept, &$skipped, &$rows, &$warningCounts, &$inactive) {
            foreach (Employee::orderBy('matricule')->get() as $employee) {
                $attrs = $employee->getAttributes();
                $name = trim(($attrs['first_name'] ?? '') . ' ' . ($attrs['last_name'] ?? '')) ?: ('#' . $employee->id);

                if (PayrollInputBuilder::presence($employee->id, $period->period) <= 0.0) {
                    $inactive++;

                    continue;
                }

                try {
                    $built = PayrollInputBuilder::build($employee->id, $period->period);
                } catch (\RuntimeException $e) {
                    $skipped[] = ['employee' => $name, 'matricule' => $attrs['matricule'] ?? null, 'reason' => $e->getMessage()];

                    continue;
                }

                $input = $built['input'];
                $result = PayrollCalculator::compute($input);
                $contract = PayrollCalculator::compute(array_merge($input, ['variables' => [], 'factor' => 1.0]))['totals']['net_contract'];
                $known = $input['salary']['target_net'];
                $check = NetCheck::classify($known, $contract, $result['totals']['net_to_pay'], $tolerance);
                $t = $result['totals'];

                $departmentId = $built['meta']['department_id'];
                $departmentName = $departmentId ? ($departments[$departmentId] ?? null) : null;
                $previous = $existing->get($employee->id);

                // Un écart déjà accepté le reste tant qu'il ne change pas.
                $keepAck = $previous && $previous->acknowledged_at && $check['status'] === NetCheck::TO_CHECK && (int) $previous->structure_gap === (int) $check['structure_gap'];

                $payslip = $previous ?? new Payslip();
                $payslip->fill([
                    'payroll_period_id' => $period->id, 'employee_id' => $employee->id, 'period' => $period->period, 'status' => 'draft',
                    'employee_name' => $name, 'matricule' => $attrs['matricule'] ?? null, 'department_id' => $departmentId, 'department_name' => $departmentName,
                    'gross' => $t['gross'], 'net_to_pay' => $t['net_to_pay'], 'net_contract' => $contract, 'net_known' => $known,
                    'variables_effect' => $check['variables_effect'], 'structure_gap' => $check['structure_gap'], 'check_status' => $check['status'],
                    'employee_contributions' => $t['employee_contributions'], 'its' => $t['its'], 'employer_total' => $t['employer_total'], 'employer_cost' => $t['employer_cost'],
                    'acknowledged_by' => $keepAck ? $previous->acknowledged_by : null,
                    'acknowledged_at' => $keepAck ? $previous->acknowledged_at : null,
                    'acknowledged_note' => $keepAck ? $previous->acknowledged_note : null,
                    'input_hash' => PayrollFingerprint::hash($input),
                    'snapshot' => [
                        'result' => $result,
                        'meta' => Arr::except($built['meta'], ['notes']),
                        'rules' => $input['rules'],
                        'salary' => $input['salary'],
                        'family' => $input['family'],
                        'loan_installment_ids' => $built['meta']['loan_installment_ids'],
                        'tolerance' => $tolerance,
                        'calculated_at' => now()->toIso8601String(),
                    ],
                ]);

                if (! $payslip->exists) {
                    $payslip->company_id = $companyId;
                }

                $payslip->save();
                $kept[] = $employee->id;
                $rows[] = ['department' => $departmentName, 'net_known' => $known, 'net_to_pay' => $t['net_to_pay'], 'status' => $check['status']];

                foreach (array_merge($built['meta']['warnings'], $result['warnings']) as $w) {
                    $warningCounts[$w] = ($warningCounts[$w] ?? 0) + 1;
                }
            }

            Payslip::where('payroll_period_id', $period->id)->whereNotIn('employee_id', $kept)->delete();

            arsort($warningCounts);

            $summary = RunSummary::summarize($rows);
            $summary['skipped'] = $skipped;
            $summary['inactive'] = $inactive;
            $summary['tolerance'] = $tolerance;
            $summary['warnings'] = array_map(fn ($text, $count) => ['text' => $text, 'count' => $count], array_keys(array_slice($warningCounts, 0, 10, true)), array_values(array_slice($warningCounts, 0, 10, true)));

            $period->update(['status' => 'calculated', 'calculated_at' => now(), 'calculated_by' => $userId, 'summary' => $summary]);
        });

        return ['processed' => count($rows), 'skipped' => $skipped, 'inactive' => $inactive];
    }

    /**
     * Clôture : refuse si un écart à vérifier n'a pas été traité ou si les données ont changé depuis le calcul.
     *
     * @throws \RuntimeException
     */
    public static function close(PayrollPeriod $period, ?int $userId): void
    {
        if ($period->status !== 'calculated') {
            throw new \RuntimeException('Calculez d\'abord la paie du mois.');
        }

        $payslips = Payslip::where('payroll_period_id', $period->id)->get();

        if ($payslips->isEmpty()) {
            throw new \RuntimeException('Aucun bulletin à clôturer.');
        }

        $pending = $payslips->filter(fn ($p) => $p->check_status === NetCheck::TO_CHECK && $p->acknowledged_at === null);

        if ($pending->isNotEmpty()) {
            throw new \RuntimeException($pending->count() . ' bulletin(s) ont un net différent du net connu et n\'ont pas été traités : ' . $pending->take(5)->pluck('employee_name')->implode(', ') . ($pending->count() > 5 ? '…' : '') . '.');
        }

        // Fraîcheur : rien ne doit avoir changé depuis le calcul (salaire, variables, règles, rubriques…).
        $stale = [];
        $byEmployee = $payslips->keyBy('employee_id');

        foreach ($payslips as $p) {
            try {
                $built = PayrollInputBuilder::build((int) $p->employee_id, $period->period);

                if ($built['meta']['factor'] <= 0.0 || $p->input_hash !== PayrollFingerprint::hash($built['input'])) {
                    $stale[] = $p->employee_name;
                }
            } catch (\RuntimeException) {
                $stale[] = $p->employee_name;
            }
        }

        foreach (Employee::orderBy('matricule')->get() as $employee) {
            if ($byEmployee->has($employee->id) || PayrollInputBuilder::presence($employee->id, $period->period) <= 0.0) {
                continue;
            }

            try {
                PayrollInputBuilder::build($employee->id, $period->period);
                $stale[] = trim(($employee->getAttributes()['first_name'] ?? '') . ' ' . ($employee->getAttributes()['last_name'] ?? '')) . ' (nouveau)';
            } catch (\RuntimeException) {
                // déjà signalé parmi les employés non traités au moment du calcul
            }
        }

        if ($stale !== []) {
            throw new \RuntimeException('Les données ont changé depuis le calcul (' . implode(', ', array_slice($stale, 0, 5)) . (count($stale) > 5 ? '…' : '') . ') : recalculez la paie avant de clôturer.');
        }

        DB::transaction(function () use ($period, $payslips, $userId) {
            foreach ($payslips as $p) {
                $p->update(['status' => 'final']);

                foreach ($p->snapshot['loan_installment_ids'] ?? [] as $installmentId) {
                    $installment = LoanInstallment::find($installmentId);

                    if ($installment && $installment->status === 'scheduled') {
                        $installment->markDeducted($period->period);
                    }
                }
            }

            $rows = $payslips->map(fn ($p) => ['department' => $p->department_name, 'net_known' => $p->net_known !== null ? (int) $p->net_known : null, 'net_to_pay' => (int) $p->net_to_pay, 'status' => $p->check_status])->all();
            $summary = array_merge($period->summary ?? [], RunSummary::summarize($rows));
            $summary['employer_cost'] = (int) $payslips->sum('employer_cost');
            $summary['gross'] = (int) $payslips->sum('gross');

            $period->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId, 'summary' => $summary]);
        });
    }

    /**
     * Régularisation d'un bulletin clôturé : une ligne confirmée et tracée sur le premier mois non clôturé qui suit.
     *
     * @throws \RuntimeException
     */
    public static function regularize(Payslip $payslip, string $direction, int $amount, string $reason, ?int $userId): PayrollVariable
    {
        if ($payslip->status !== 'final') {
            throw new \RuntimeException('Seul un bulletin de paie clôturée se régularise : sinon, corrigez les données et recalculez.');
        }

        if ($amount <= 0 || trim($reason) === '') {
            throw new \RuntimeException('Indiquez un montant et le motif de la régularisation.');
        }

        $target = LoanSchedule::addMonths($payslip->period, 1);

        for ($i = 0; $i < 12 && PayrollPeriodGuard::isClosed($target); $i++) {
            $target = LoanSchedule::addMonths($target, 1);
        }

        if (PayrollPeriodGuard::isClosed($target)) {
            throw new \RuntimeException('Aucun mois ouvert trouvé après ce bulletin.');
        }

        $item = self::regularisationItem($direction === 'add' ? 'regularisation_gain' : 'regularisation_deduction', (int) $payslip->company_id);
        [$year, $month] = explode('-', $payslip->period);

        $variable = new PayrollVariable([
            'employee_id' => $payslip->employee_id,
            'period' => $target,
            'kind' => $direction === 'add' ? 'bonus' : 'deduction',
            'pay_item_id' => $item->id,
            'amount_override' => $amount,
            'source_type' => 'regularisation',
            'source_id' => $payslip->id,
            'source_ref' => 'Régularisation du bulletin de ' . $month . '/' . $year . ' : ' . mb_substr(trim($reason), 0, 180),
            'note' => mb_substr(trim($reason), 0, 500),
            'status' => 'confirmed',
            'created_by' => $userId,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
        $variable->company_id = $payslip->company_id;
        $variable->save();

        return $variable;
    }

    protected static function regularisationItem(string $key, int $companyId): PayItem
    {
        $item = PayItem::where('system_key', $key)->first();

        if ($item) {
            return $item;
        }

        [$code, $label, $type, $flag] = $key === 'regularisation_gain'
            ? ['100900', 'Régularisation (gain)', 'gain', true]
            : ['390900', 'Régularisation (retenue)', 'deduction_individual', false];

        for ($i = 0; $i < 30 && PayItem::where('code', $code)->exists(); $i++) {
            $code = (string) ((int) $code + 1);
        }

        $item = new PayItem([
            'code' => $code, 'label' => $label, 'type' => $type, 'system_key' => $key, 'is_system' => true,
            'subject_to_social' => $flag, 'taxable' => $flag, 'is_compulsory' => false, 'calc_type' => 'system',
            'show_on_payslip' => true, 'sort' => $flag ? 950 : 960, 'is_active' => true, 'notes' => 'Correction d\'une paie clôturée : montant et motif saisis à la régularisation.',
        ]);
        $item->company_id = $companyId;
        $item->save();

        return $item;
    }

    protected static function labelOf(object $model): string
    {
        $attrs = $model->getAttributes();

        foreach (['name', 'label', 'title'] as $key) {
            if (! empty($attrs[$key])) {
                return (string) $attrs[$key];
            }
        }

        return '#' . $model->getKey();
    }
}
