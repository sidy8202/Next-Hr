<?php

namespace App\Services\Payroll;

use App\Models\PayItem;
use App\Models\PayrollVariableSetting;

/**
 * Mode d'affichage de la paie : « simple » (options rares masquées) ou « advanced » (tout visible).
 * La partie pure décide quoi montrer ; la partie base lit et écrit le choix de l'entreprise.
 *
 * Règle de sécurité : le mode simple ne cache JAMAIS ce que l'entreprise utilise déjà. Un type de calcul avancé reste choisissable
 * s'il est celui de la rubrique en cours de modification ; un plancher ou un plafond déjà saisi reste visible ; un salaire en
 * « net garanti » garde son option. Le mode ne change aucun calcul : seulement ce que les formulaires proposent.
 */
class PayrollUiMode
{
    public const SIMPLE = 'simple';
    public const ADVANCED = 'advanced';

    /** Types de calcul proposés en mode simple (les autres : % d'une rubrique, quantité × taux, barème d'ancienneté). */
    public const SIMPLE_CALC_TYPES = ['fixed', 'percent_base', 'percent_gross'];

    /** Types de calcul qui n'ont de sens qu'en mode avancé. */
    public const ADVANCED_CALC_TYPES = ['qty_rate', 'percent_item', 'scale'];

    /** @var array<int, string> */
    protected static array $cache = [];

    // ───── Partie pure ─────

    public static function normalize(?string $value): ?string
    {
        return in_array($value, [self::SIMPLE, self::ADVANCED], true) ? $value : null;
    }

    /** Valeur enregistrée si elle est valide ; sinon « avancé » pour qui utilise déjà des options avancées, « simple » pour les autres. */
    public static function resolve(?string $stored, bool $usesAdvanced): string
    {
        return self::normalize($stored) ?? ($usesAdvanced ? self::ADVANCED : self::SIMPLE);
    }

    /**
     * @param array<string, string> $all tous les types de calcul (clé => libellé)
     *
     * @return array<string, string>
     */
    public static function calcTypes(array $all, bool $simple, ?string $current): array
    {
        if (! $simple) {
            return $all;
        }

        return array_filter(
            $all,
            fn ($label, $key) => in_array($key, self::SIMPLE_CALC_TYPES, true) || $key === 'system' || $key === $current,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** Plancher et plafond d'une rubrique : masqués en mode simple, sauf s'ils sont déjà saisis. */
    public static function showLimits(bool $simple, bool $hasLimits): bool
    {
        return ! $simple || $hasLimits;
    }

    /** Option « net garanti » d'un salaire : masquée en mode simple, sauf si le salaire est déjà en net garanti. */
    public static function showNetTarget(bool $simple, string $currentMode): bool
    {
        return ! $simple || $currentMode === 'net_target';
    }

    // ───── Partie base de données ─────

    public static function mode(int $companyId): string
    {
        if (isset(self::$cache[$companyId])) {
            return self::$cache[$companyId];
        }

        try {
            $stored = PayrollVariableSetting::where('company_id', $companyId)->value('ui_mode');

            return self::$cache[$companyId] = self::resolve($stored !== null ? (string) $stored : null, $stored === null && self::usesAdvanced($companyId));
        } catch (\Throwable) {
            // migration pas encore passée : on montre tout, rien ne se cache
            return self::ADVANCED;
        }
    }

    public static function isSimple(int $companyId): bool
    {
        return self::mode($companyId) === self::SIMPLE;
    }

    public static function set(int $companyId, string $mode): void
    {
        $mode = self::normalize($mode);

        if ($mode === null) {
            return;
        }

        PayrollVariableSetting::forCompany($companyId)->forceFill(['ui_mode' => $mode])->save();
        self::$cache[$companyId] = $mode;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /** Une entreprise sans réglages enregistrés qui utilise déjà des rubriques avancées garde l'affichage complet. */
    protected static function usesAdvanced(int $companyId): bool
    {
        return PayItem::where('company_id', $companyId)
            ->where(fn ($q) => $q->whereIn('calc_type', self::ADVANCED_CALC_TYPES)->orWhereNotNull('floor_amount')->orWhereNotNull('cap_amount'))
            ->exists();
    }
}
