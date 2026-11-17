<?php

namespace App\Services\Reports;

/**
 * Garde-fou des rapports : aucune lecture de saisonnalité sous 12 mois d'historique (une seule année ne montre pas une saison).
 * Fonctions pures. Servira aux rapports de congés (18b) et de départs (19c) ; le rapport de paie l'affiche déjà en pied de page.
 */
final class HistoryGuard
{
    public const MIN_MONTHS_SEASONALITY = 12;

    /**
     * Nombre de mois couverts, bornes comprises, entre deux périodes AAAA-MM ; 0 si l'historique est vide ou incohérent.
     */
    public static function monthsOfHistory(?string $firstPeriod, ?string $lastPeriod): int
    {
        if ($firstPeriod === null || $lastPeriod === null
            || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $firstPeriod, $a)
            || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $lastPeriod, $b)) {
            return 0;
        }

        $diff = ((int) $b[1] * 12 + (int) $b[2]) - ((int) $a[1] * 12 + (int) $a[2]);

        return $diff < 0 ? 0 : $diff + 1;
    }

    public static function allowsSeasonality(int $months): bool
    {
        return $months >= self::MIN_MONTHS_SEASONALITY;
    }

    /** Phrase de pied de page quand la lecture saisonnière est interdite, sinon null. */
    public static function seasonalityNote(int $months): ?string
    {
        if (self::allowsSeasonality($months)) {
            return null;
        }

        return 'Pas de lecture saisonnière : l\'historique couvre ' . $months . ' mois, il en faut au moins ' . self::MIN_MONTHS_SEASONALITY . '.';
    }
}
