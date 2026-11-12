<?php

namespace App\Services\Payroll;

/** Prorata de présence d'un mois (entrée ou sortie en cours de mois) : fonction pure. */
final class Proration
{
    /**
     * Facteur de présence, sur la base de jours des paramètres (30) : 1 pour un mois complet.
     * Les jours calendaires du mois où l'employé n'est pas là sont retirés de la base : arrivé le 16 d'un mois, il reste 15 jours sur 30.
     *
     * La politique de l'entreprise peut toutefois faire payer le mois entier :
     *  - arrivée : « prorate » (toujours retirer les jours avant l'arrivée), « full » (toujours payer le mois entier)
     *    ou « threshold » (mois entier si l'employé arrive au plus tard le N du mois, sinon prorata) ;
     *  - départ : « prorate », « full » ou « threshold » (mois entier si le départ tombe dans les N derniers jours du mois).
     *
     * @param string|null $hire  première date de présence (AAAA-MM-JJ), null = déjà là
     * @param string|null $exit  dernière date de présence, null = encore là
     * @param array{arrival?: array{rule?: string, days?: int}, departure?: array{rule?: string, days?: int}} $policy
     */
    public static function presenceFactor(?string $hire, ?string $exit, string $monthStart, string $monthEnd, int $daysBasis = 30, array $policy = []): float
    {
        $from = ($hire !== null && $hire > $monthStart) ? $hire : $monthStart;
        $to = ($exit !== null && $exit < $monthEnd) ? $exit : $monthEnd;

        // Pas présent un seul jour du mois : rien à payer, quelle que soit la politique.
        if ($to < $from || $daysBasis <= 0) {
            return 0.0;
        }

        $daysInMonth = PayrollDays::countDays($monthStart, $monthEnd, 'calendar');

        if ($from > $monthStart && self::waived($policy['arrival'] ?? [], (int) substr($from, 8, 2), $daysInMonth, true)) {
            $from = $monthStart;
        }

        if ($to < $monthEnd && self::waived($policy['departure'] ?? [], (int) substr($to, 8, 2), $daysInMonth, false)) {
            $to = $monthEnd;
        }

        $present = PayrollDays::countDays($from, $to, 'calendar');
        $absent = $daysInMonth - $present;

        return $absent <= 0 ? 1.0 : max(0.0, ($daysBasis - $absent) / $daysBasis);
    }

    /**
     * Le jour d'arrivée (ou de départ) donne-t-il droit au mois entier selon la politique ?
     * Arrivée « threshold » : jour d'arrivée ≤ N. Départ « threshold » : moins de N jours restent après le départ.
     *
     * @param array{rule?: string, days?: int} $policy
     */
    public static function waived(array $policy, int $day, int $daysInMonth, bool $arrival): bool
    {
        $rule = $policy['rule'] ?? 'prorate';

        if ($rule === 'full') {
            return true;
        }

        if ($rule !== 'threshold') {
            return false;
        }

        $n = max(0, (int) ($policy['days'] ?? 0));

        return $arrival ? $day <= $n : ($daysInMonth - $day) < $n;
    }
}
