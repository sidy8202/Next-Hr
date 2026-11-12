<?php

namespace App\Services\Payroll;

/**
 * Prime d'une affectation payée selon le salaire du poste occupé : fonctions pures.
 * Même règle de proratisation que la prime mensuelle de l'écran Affectations : jours d'affectation dans le mois ÷ jours du mois.
 */
final class AssignmentPay
{
    /** Part du mois couverte par l'affectation (0 à 1). */
    public static function fraction(int $daysInPeriod, int $daysInMonth): float
    {
        return $daysInMonth > 0 ? min(1.0, max(0.0, $daysInPeriod / $daysInMonth)) : 0.0;
    }

    /**
     * Montant d'une affectation « salaire du poste » pour le mois.
     *  - difference : (salaire du poste − salaire de référence de l'employé) × part du mois, jamais négatif ;
     *  - full : salaire entier du poste × part du mois.
     */
    public static function positionSalaryAmount(float $positionSalary, float $employeeReference, int $daysInPeriod, int $daysInMonth, string $basis = 'difference', string $rounding = 'nearest'): int
    {
        $base = $basis === 'full' ? $positionSalary : max(0.0, $positionSalary - $employeeReference);

        return IncomeTax::round($base * self::fraction($daysInPeriod, $daysInMonth), $rounding);
    }

    /** Montant d'une prime fixée, déjà calculé par AssignmentService::allowanceLines() : on ne fait qu'arrondir selon le réglage. */
    public static function allowanceAmount(float $computed, string $rounding = 'nearest'): int
    {
        return IncomeTax::round($computed, $rounding);
    }
}
