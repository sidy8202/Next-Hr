<?php

namespace App\Services\Payroll;

use DateInterval;
use DateTimeImmutable;

/**
 * Calculs de jours et d'heures de la paie : fonctions pures (aucune lecture de base).
 * Les montants de référence (base 30 jours, heures du mois, taux de majoration, jours de repos) viennent des réglages, jamais d'ici.
 */
final class PayrollDays
{
    /** Valeur d'un jour : salaire mensuel de référence ÷ base de jours (30 par défaut dans les réglages). */
    public static function dailyRate(float $monthlyPay, int $daysBasis): float
    {
        return $daysBasis > 0 ? $monthlyPay / $daysBasis : 0.0;
    }

    /** Valeur d'une heure : salaire mensuel de référence ÷ heures du mois. */
    public static function hourlyRate(float $monthlyPay, float $hoursMonth): float
    {
        return $hoursMonth > 0 ? $monthlyPay / $hoursMonth : 0.0;
    }

    /** Retenue pour des jours d'absence ou de mise à pied. */
    public static function deduction(float $monthlyPay, int $daysBasis, float $days, string $rounding = 'nearest'): int
    {
        return IncomeTax::round(self::dailyRate($monthlyPay, $daysBasis) * max(0.0, $days), $rounding);
    }

    /** Heures supplémentaires : heures × valeur d'une heure × (1 + majoration). */
    public static function overtimeAmount(float $monthlyPay, float $hoursMonth, float $hours, float $majorationPercent, string $rounding = 'nearest'): int
    {
        return IncomeTax::round(self::hourlyRate($monthlyPay, $hoursMonth) * max(0.0, $hours) * (1 + $majorationPercent / 100), $rounding);
    }

    /** Jours travaillés payés : jours × valeur d'un jour × (1 + majoration éventuelle). */
    public static function workedDaysAmount(float $monthlyPay, int $daysBasis, float $days, float $majorationPercent, string $rounding = 'nearest'): int
    {
        return IncomeTax::round(self::dailyRate($monthlyPay, $daysBasis) * max(0.0, $days) * (1 + $majorationPercent / 100), $rounding);
    }

    /** Premier et dernier jour d'un mois « AAAA-MM ». @return array{0: string, 1: string} */
    public static function monthBounds(string $period): array
    {
        $first = new DateTimeImmutable($period . '-01');

        return [$first->format('Y-m-d'), $first->modify('last day of this month')->format('Y-m-d')];
    }

    /**
     * Nombre de jours d'une période [from, to] qui tombent dans la fenêtre [winFrom, winTo] (le mois de paie en général).
     * Mode « calendar » : tous les jours. Mode « working » : sauf les jours de repos (0 = dimanche) et les jours fériés donnés.
     *
     * @param array<int, int>    $restWeekdays
     * @param array<int, string> $holidays dates AAAA-MM-JJ
     */
    public static function countDays(string $from, string $to, string $mode, array $restWeekdays = [0], array $holidays = [], ?string $winFrom = null, ?string $winTo = null): int
    {
        $start = $winFrom !== null && $winFrom > $from ? $winFrom : $from;
        $end = $winTo !== null && $winTo < $to ? $winTo : $to;

        if ($end < $start) {
            return 0;
        }

        $day = new DateTimeImmutable($start);
        $last = new DateTimeImmutable($end);
        $count = 0;

        while ($day <= $last) {
            $isRest = in_array((int) $day->format('w'), $restWeekdays, true) || in_array($day->format('Y-m-d'), $holidays, true);

            if ($mode === 'calendar' || ! $isRest) {
                $count++;
            }

            $day = $day->add(new DateInterval('P1D'));
        }

        return $count;
    }

    /** Part d'une absence ou d'un congé qui tombe dans le mois, au prorata des jours calendaires : 6 jours sur 6 jours dont 3 dans le mois = 3. */
    public static function proportionalDays(float $totalDays, string $from, string $to, string $winFrom, string $winTo): float
    {
        $span = self::countDays($from, $to, 'calendar');

        if ($span === 0 || $totalDays <= 0) {
            return 0.0;
        }

        $inside = self::countDays($from, $to, 'calendar', [], [], $winFrom, $winTo);

        return round($totalDays * $inside / $span, 2);
    }
}
