<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;

/** Une paie clôturée est définitive : ses variables ne se modifient plus (on régularise le mois suivant). */
final class PayrollPeriodGuard
{
    public static function isClosed(?string $period): bool
    {
        return $period !== null && PayrollPeriod::where('period', $period)->where('status', 'closed')->exists();
    }

    public static function assertOpen(?string $period): void
    {
        if (self::isClosed($period)) {
            throw new \RuntimeException('La paie de ce mois est clôturée : elle ne se modifie plus. Utilisez une régularisation sur le mois suivant.');
        }
    }
}
