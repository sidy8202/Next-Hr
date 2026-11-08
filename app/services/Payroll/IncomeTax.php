<?php

namespace App\Services\Payroll;

/**
 * Impôt sur les traitements et salaires (ITS), selon le Code général des impôts du Mali :
 * art. 9 et 10 (barème par tranches), art. 11 (réduction pour charges de famille), art. 12-A et 12-B (réduction de 2 points sur le taux réel).
 * Fonctions pures : le barème et les pourcentages viennent des règles (données datées), jamais du code.
 */
final class IncomeTax
{
    /** Impôt brut annuel. $brackets = [[de, à (null = sans limite), taux en %], …]. */
    public static function grossAnnual(float $base, array $brackets): float
    {
        $tax = 0.0;

        foreach ($brackets as [$from, $to, $rate]) {
            if ($base <= $from) {
                break;
            }

            $upper = $to === null ? $base : min($base, (float) $to);
            $tax += ($upper - $from) * $rate / 100;
        }

        return $tax;
    }

    /** Marié(e) ? Reconnaît les écritures courantes ; célibataire, divorcé, veuf, séparé = non. */
    public static function isMarried(?string $status): bool
    {
        $s = mb_strtolower(trim((string) $status));

        if ($s === '') {
            return false;
        }

        foreach (['célib', 'celib', 'single', 'divorc', 'veuf', 'veuve', 'widow', 'séparé', 'separe'] as $no) {
            if (str_contains($s, $no)) {
                return false;
            }
        }

        return str_contains($s, 'mari') || str_contains($s, 'married');
    }

    /** Réduction pour charges de famille, en % de l'impôt brut. */
    public static function familyPercent(bool $married, int $children, array $family = [], int $infirmAdults = 0): float
    {
        $kids = min(max(0, $children), (int) ($family['max_children'] ?? 10));

        return ($married ? (float) ($family['married'] ?? 10) : 0.0)
            + $kids * (float) ($family['per_child'] ?? 2.5)
            + max(0, $infirmAdults) * (float) ($family['infirm_adult'] ?? 10);
    }

    /**
     * ITS annuel : impôt brut, moins la réduction de famille (= impôt liquidé),
     * puis taux réel (impôt liquidé ÷ revenu imposable) diminué de 2 points, appliqué au revenu imposable.
     */
    public static function annual(float $annualBase, array $params, bool $married, int $children, int $infirmAdults = 0): float
    {
        if ($annualBase <= 0) {
            return 0.0;
        }

        $gross = self::grossAnnual($annualBase, $params['brackets'] ?? []);
        $reduction = min(100.0, self::familyPercent($married, $children, $params['family'] ?? [], $infirmAdults));
        $liquidated = $gross * (1 - $reduction / 100);
        $rebate = (float) ($params['rebate_points'] ?? 2) / 100 * $annualBase;

        return max(0.0, $liquidated - $rebate);
    }

    /** ITS d'un mois : base mensuelle annualisée (×12), impôt annuel ÷ 12, arrondi. */
    public static function monthly(float $monthlyBase, array $params, bool $married, int $children, string $rounding = 'nearest', int $infirmAdults = 0): int
    {
        return self::round(self::annual($monthlyBase * 12, $params, $married, $children, $infirmAdults) / 12, $rounding);
    }

    public static function round(float $value, string $mode = 'nearest'): int
    {
        return match ($mode) {
            'floor' => (int) floor($value),
            'ceil' => (int) ceil($value),
            default => (int) round($value),
        };
    }
}
