<?php

namespace App\Services\Payroll;

/** Cumuls de l'année d'un employé, affichés en bas du bulletin : fonction pure. */
final class PayslipCumuls
{
    /**
     * Additionne les bulletins de l'année de $period, jusqu'à $period inclus.
     *
     * @param  array<int, array{period: string, gross: int, its_base: int, employee_contributions: int, its: int, net_to_pay: int}>  $rows
     * @return array{months: int, gross: int, taxable_base: int, contributions: int, its: int, net: int}
     */
    public static function upTo(array $rows, string $period): array
    {
        $year = substr($period, 0, 4);
        $sum = ['months' => 0, 'gross' => 0, 'taxable_base' => 0, 'contributions' => 0, 'its' => 0, 'net' => 0];

        foreach ($rows as $r) {
            if (substr($r['period'], 0, 4) !== $year || $r['period'] > $period) {
                continue;
            }

            $sum['months']++;
            $sum['gross'] += $r['gross'];
            $sum['taxable_base'] += $r['its_base'];
            $sum['contributions'] += $r['employee_contributions'];
            $sum['its'] += $r['its'];
            $sum['net'] += $r['net_to_pay'];
        }

        return $sum;
    }
}
