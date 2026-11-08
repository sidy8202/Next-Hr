<?php

namespace App\Services\Payroll;

/** Échéanciers de prêts : fonctions pures. On stocke le MONTANT de l'échéance, jamais un pourcentage. */
final class LoanSchedule
{
    /** Ajoute (ou retire) des mois à un mois « AAAA-MM ». */
    public static function addMonths(string $period, int $months): string
    {
        [$year, $month] = array_map('intval', explode('-', $period));
        $index = $year * 12 + ($month - 1) + $months;

        return sprintf('%04d-%02d', intdiv($index, 12), $index % 12 + 1);
    }

    /**
     * Échéancier : échéances égales, la dernière ajustée au reste.
     *
     * @return array<int, array{month: string, amount: int}>
     */
    public static function generate(int $principal, int $installment, string $firstMonth): array
    {
        if ($principal <= 0 || $installment <= 0) {
            return [];
        }

        $count = (int) ceil($principal / $installment);
        $left = $principal;
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $amount = min($installment, $left);
            $rows[] = ['month' => self::addMonths($firstMonth, $i), 'amount' => $amount];
            $left -= $amount;
        }

        return $rows;
    }

    /** Taux d'effort indicatif : échéance ÷ salaire de référence, en %. Null si le salaire est inconnu. */
    public static function effortPercent(float $installment, float $monthlyPay): ?float
    {
        return $monthlyPay > 0 ? round($installment / $monthlyPay * 100, 1) : null;
    }

    /**
     * Remboursement anticipé : le montant se retranche des DERNIÈRES échéances restantes.
     *
     * @param  array<int, int>  $amounts montants des échéances restantes, dans l'ordre
     * @return array{amounts: array<int, int>, surplus: int} échéances soldées = 0 ; surplus = montant qui dépasse le solde
     */
    public static function applyEarlyRepayment(array $amounts, int $repaid): array
    {
        $amounts = array_values($amounts);
        $left = max(0, $repaid);

        for ($i = count($amounts) - 1; $i >= 0 && $left > 0; $i--) {
            $take = min($amounts[$i], $left);
            $amounts[$i] -= $take;
            $left -= $take;
        }

        return ['amounts' => $amounts, 'surplus' => $left];
    }

    /**
     * Reprise après suspension : les échéances suspendues repartent sur des mois successifs.
     *
     * @return array<int, string>
     */
    public static function reschedule(int $count, string $resumeMonth): array
    {
        $months = [];

        for ($i = 0; $i < $count; $i++) {
            $months[] = self::addMonths($resumeMonth, $i);
        }

        return $months;
    }
}
