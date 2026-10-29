<?php

namespace App\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Calculs de perdiem, sans accès base : testable seul.
 * Montants en FCFA entiers.
 */
final class PerdiemCalculator
{
    public const ALLOWED_DIVISORS = [22, 26, 30];

    /** Nombre de jours calendaires, bornes incluses. */
    public static function days(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end->startOfDay()->lt($start->startOfDay())) {
            throw new InvalidArgumentException('La date de retour précède la date de départ.');
        }

        return (int) $start->startOfDay()->diffInDays($end->startOfDay(), true) + 1;
    }

    /** Salaire journalier = salaire mensuel de référence / diviseur, arrondi à l'entier. */
    public static function dailyFromMonthlySalary(int $monthlySalary, int $divisor = 30): int
    {
        if (! in_array($divisor, self::ALLOWED_DIVISORS, true)) {
            throw new InvalidArgumentException("Diviseur non autorisé : {$divisor}.");
        }
        if ($monthlySalary < 0) {
            throw new InvalidArgumentException('Le salaire ne peut pas être négatif.');
        }

        return (int) round($monthlySalary / $divisor);
    }

    public static function total(int $dailyRate, int $days): int
    {
        if ($dailyRate < 0 || $days < 1) {
            throw new InvalidArgumentException('Taux ou nombre de jours invalide.');
        }

        return $dailyRate * $days;
    }
}
