<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\Payslip;

/**
 * Lecture SEULE de l'historique et des répartitions du tableau de bord paie (aucune écriture, aucun recalcul).
 * Seuls les mois calculés ou clôturés ont des chiffres ; les autres n'apparaissent pas (trou dans la courbe).
 */
final class PayrollDashboardHistory
{
    /**
     * Totaux des 13 mois qui se terminent à $end : les 12 de la courbe, plus le même mois de l'an dernier pour la comparaison.
     * Une seule requête de sommes, groupée par mois.
     *
     * @return array<string, array{status: string, totals: array<string, int>}>
     */
    public static function months(int $companyId, string $end): array
    {
        $periods = PayrollTrend::periods($end, PayrollTrend::WINDOW + 1);

        $statuses = PayrollPeriod::where('company_id', $companyId)->whereIn('period', $periods)->pluck('status', 'period')->all();
        $calculated = array_keys(array_filter($statuses, fn ($s) => in_array($s, ['calculated', 'closed'], true)));

        if ($calculated === []) {
            return [];
        }

        $rows = Payslip::where('company_id', $companyId)
            ->whereIn('period', $calculated)
            ->toBase()
            ->selectRaw('period, COUNT(*) as n, COALESCE(SUM(gross), 0) as gross, COALESCE(SUM(net_to_pay), 0) as net_to_pay, COALESCE(SUM(employee_contributions), 0) as employee_contributions, COALESCE(SUM(its), 0) as its, COALESCE(SUM(employer_total), 0) as employer_total, COALESCE(SUM(employer_cost), 0) as employer_cost')
            ->groupBy('period')
            ->get();

        $out = [];

        foreach ($rows as $row) {
            if ((int) $row->n === 0) {
                continue;
            }

            $out[(string) $row->period] = [
                'status' => (string) $statuses[(string) $row->period],
                'totals' => PayrollDashboardStats::totals([
                    'count' => (int) $row->n, 'gross' => (int) $row->gross, 'net_to_pay' => (int) $row->net_to_pay,
                    'employee_contributions' => (int) $row->employee_contributions, 'its' => (int) $row->its,
                    'employer_total' => (int) $row->employer_total, 'employer_cost' => (int) $row->employer_cost,
                ]) + ['its' => (int) $row->its],
            ];
        }

        return $out;
    }

    /**
     * Une ligne par service pour une paie : effectif et sommes. Le nom est celui figé sur le bulletin.
     *
     * @return array<int, array{name: ?string, count: int, net_to_pay: int, gross: int, employer_cost: int}>
     */
    public static function departments(int $companyId, int $periodId): array
    {
        $rows = Payslip::where('company_id', $companyId)
            ->where('payroll_period_id', $periodId)
            ->toBase()
            ->selectRaw('department_name, COUNT(*) as n, COALESCE(SUM(gross), 0) as gross, COALESCE(SUM(net_to_pay), 0) as net_to_pay, COALESCE(SUM(employer_cost), 0) as employer_cost')
            ->groupBy('department_name')
            ->get();

        return $rows->map(fn ($r) => [
            'name' => $r->department_name,
            'count' => (int) $r->n,
            'net_to_pay' => (int) $r->net_to_pay,
            'gross' => (int) $r->gross,
            'employer_cost' => (int) $r->employer_cost,
        ])->all();
    }

    /**
     * Rubriques du mois, additionnées sur tous les bulletins, lues dans les snapshots (gains puis retenues sur le brut).
     * Lecture par paquets de 100 bulletins ; seule la paie affichée est lue.
     *
     * @return array<int, array{key: string, label: string, kind: string, amount: int}>
     */
    public static function components(int $companyId, int $periodId): array
    {
        $agg = [];

        Payslip::where('company_id', $companyId)
            ->where('payroll_period_id', $periodId)
            ->select('id', 'snapshot')
            ->chunkById(100, function ($payslips) use (&$agg) {
                foreach ($payslips as $p) {
                    $result = is_array($p->snapshot['result'] ?? null) ? $p->snapshot['result'] : [];

                    foreach (['earnings' => 'earning', 'gross_deductions' => 'deduction'] as $bucket => $kind) {
                        foreach ((array) ($result[$bucket] ?? []) as $line) {
                            $key = $kind . ':' . (string) ($line['code'] ?? $line['label'] ?? '?');
                            $agg[$key] ??= ['key' => $key, 'label' => (string) ($line['label'] ?? $line['code'] ?? 'Rubrique'), 'kind' => $kind, 'amount' => 0];
                            $agg[$key]['amount'] += (int) ($line['amount'] ?? 0);
                        }
                    }
                }
            });

        return array_values($agg);
    }
}
