<?php

namespace App\Services\Payroll;

/** Prorata de présence d'un mois (entrée ou sortie en cours de mois) : fonction pure. */
final class Proration
{
    /**
     * Facteur de présence, sur la base de jours des paramètres (30) : 1 pour un mois complet.
     * Les jours calendaires du mois où l'employé n'est pas là sont retirés de la base : arrivé le 16 d'un mois, il reste 15 jours sur 30.
     *
     * @param string|null $hire  première date de présence (AAAA-MM-JJ), null = déjà là
     * @param string|null $exit  dernière date de présence, null = encore là
     */
    public static function presenceFactor(?string $hire, ?string $exit, string $monthStart, string $monthEnd, int $daysBasis = 30): float
    {
        $from = ($hire !== null && $hire > $monthStart) ? $hire : $monthStart;
        $to = ($exit !== null && $exit < $monthEnd) ? $exit : $monthEnd;

        if ($to < $from || $daysBasis <= 0) {
            return 0.0;
        }

        $present = PayrollDays::countDays($from, $to, 'calendar');
        $daysInMonth = PayrollDays::countDays($monthStart, $monthEnd, 'calendar');
        $absent = $daysInMonth - $present;

        return $absent <= 0 ? 1.0 : max(0.0, ($daysBasis - $absent) / $daysBasis);
    }
}
