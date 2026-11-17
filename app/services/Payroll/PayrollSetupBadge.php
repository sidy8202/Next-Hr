<?php

namespace App\Services\Payroll;

use App\Models\PayrollSetupState;

/**
 * Nombre d'étapes obligatoires restantes de l'assistant, pour la pastille du menu.
 * Renvoie 0 (donc pas de pastille) si l'utilisateur n'a pas le droit, si l'assistant est masqué, terminé, ou en cas d'erreur.
 * Mémorisé pour la durée de la requête : le menu peut l'appeler plusieurs fois sans requêtes en plus.
 */
class PayrollSetupBadge
{
    /** @var array<int, int> */
    protected static array $cache = [];

    public static function count(): int
    {
        try {
            $user = auth()->user();

            if (! $user || ! $user->company_id || ! $user->canDo('read', 'payroll_settings')) {
                return 0;
            }

            $companyId = (int) $user->company_id;

            if (isset(self::$cache[$companyId])) {
                return self::$cache[$companyId];
            }

            $state = PayrollSetupState::peek($companyId);

            if ($state && $state->is_hidden) {
                return self::$cache[$companyId] = 0;
            }

            return self::$cache[$companyId] = SetupProgress::evaluate(SetupFacts::facts($companyId))['remaining'];
        } catch (\Throwable) {
            return 0; // la pastille ne doit jamais casser le menu
        }
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
