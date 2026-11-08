<?php

namespace App\Services\Payroll;

/**
 * Partie LÉGALE d'une paie : cotisations salariales et patronales, base de l'ITS, ITS, net avant retenues internes.
 * Fonction pure : reçoit les règles (tableaux) et les montants ; ne lit ni la base ni la date.
 *
 * $in : gross (brut total), cotisable (assiette des cotisations, défaut = brut), taxable_gross (brut imposable avant déduction des
 *       cotisations déductibles, défaut = brut), marital_status, children, infirm_adults.
 */
final class PayrollLegal
{
    public static function compute(array $rules, array $in, string $rounding = 'nearest'): array
    {
        $gross = (float) $in['gross'];
        $cotisable = (float) ($in['cotisable'] ?? $gross);
        $taxableGross = (float) ($in['taxable_gross'] ?? $gross);

        $lines = [];
        $employeeTotal = 0;
        $employerTotal = 0;
        $deductible = 0;
        $its = null;

        foreach ($rules as $r) {
            if (($r['kind'] ?? 'rate') === 'its') {
                $its = $r;
                continue;
            }

            $base = ($r['base'] ?? 'cotisable') === 'brut' ? $gross : $cotisable;

            if (! empty($r['ceiling_monthly'])) {
                $base = min($base, (float) $r['ceiling_monthly']);
            }

            $amount = IncomeTax::round($base * (float) $r['rate'] / 100, $rounding);
            $lines[] = ['code' => $r['code'], 'label' => $r['label'], 'party' => $r['party'], 'base' => (int) round($base), 'rate' => (float) $r['rate'], 'amount' => $amount];

            if ($r['party'] === 'employee') {
                $employeeTotal += $amount;
                $deductible += ! empty($r['deductible_from_its']) ? $amount : 0;
            } else {
                $employerTotal += $amount;
            }
        }

        $itsBase = max(0.0, $taxableGross - $deductible);
        $itsAmount = 0;

        if ($its) {
            $itsAmount = IncomeTax::monthly($itsBase, $its['params'] ?? [], IncomeTax::isMarried($in['marital_status'] ?? null), (int) ($in['children'] ?? 0), $rounding, (int) ($in['infirm_adults'] ?? 0));
            $lines[] = ['code' => $its['code'], 'label' => $its['label'], 'party' => 'employee', 'base' => (int) round($itsBase), 'rate' => null, 'amount' => $itsAmount];
            $employeeTotal += $itsAmount;
        }

        return [
            'lines' => $lines,
            'its_base' => (int) round($itsBase),
            'its' => $itsAmount,
            'employee_total' => $employeeTotal,
            'employer_total' => $employerTotal,
            'net_before_other' => (int) round($gross) - $employeeTotal,
            'employer_cost' => (int) round($gross) + $employerTotal,
        ];
    }
}
