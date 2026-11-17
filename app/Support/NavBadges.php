<?php

namespace App\Support;

use App\Services\Payroll\PayrollSetupBadge;

/**
 * Pastilles du menu : une clé 'badge' dans config/navigation.php désigne un compteur. Une clé inconnue ou null ne donne rien.
 */
class NavBadges
{
    public static function count(?string $key): int
    {
        return match ($key) {
            'payroll_setup' => PayrollSetupBadge::count(),
            default => 0,
        };
    }
}
