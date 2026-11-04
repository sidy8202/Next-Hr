<?php

namespace App\Services;

use InvalidArgumentException;

/** Calculs de prix : purs (sans base de données), donc testables seuls. */
final class PlanPricing
{
    public static function net(int $listPrice, int $discountPercent): int
    {
        if ($listPrice < 0 || $discountPercent < 0 || $discountPercent > 100) {
            throw new InvalidArgumentException('Prix ou remise invalide.');
        }

        return (int) round($listPrice * (100 - $discountPercent) / 100);
    }

    /** Équivalent mensuel d'un prix de période (revenu mensuel récurrent). */
    public static function monthly(int $price, int $months): int
    {
        return $months > 0 ? intdiv($price, $months) : 0;
    }

    public static function fcfa(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
