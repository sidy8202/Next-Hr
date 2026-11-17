<?php

namespace App\Services\Reports;

/**
 * Seuils de lecture des phrases d'interprétation. Valeurs par défaut dans le code, ajustables par entreprise (table report_settings).
 * Fonctions pures : une valeur absente, inconnue ou hors bornes retombe sur le défaut.
 *
 * Le garde-fou de confidentialité (groupes de moins de 3 personnes masqués) n'est PAS un seuil : il n'est pas modifiable.
 */
final class ReportThresholds
{
    /** @var array<string, array{label: string, default: int, min: int, max: int, unit: string, report: string}> */
    public const DEFINITIONS = [
        'cost_change_notice_percent' => ['label' => 'Variation du coût jugée notable', 'default' => 5, 'min' => 1, 'max' => 50, 'unit' => '%', 'report' => 'payroll_cost'],
        'cost_trend_months' => ['label' => 'Recul de la comparaison du coût', 'default' => 3, 'min' => 1, 'max' => 12, 'unit' => 'mois', 'report' => 'payroll_cost'],
        'dept_concentration_percent' => ['label' => 'Part d’un service jugée concentrée', 'default' => 40, 'min' => 10, 'max' => 90, 'unit' => '%', 'report' => 'payroll_cost'],
        'grid_gap_alert_percent' => ['label' => 'Part d’employés sous le minimum jugée préoccupante', 'default' => 10, 'min' => 1, 'max' => 100, 'unit' => '%', 'report' => 'grid_gaps'],
    ];

    /** @return array<string, int> */
    public static function defaults(): array
    {
        return array_map(fn ($d) => $d['default'], self::DEFINITIONS);
    }

    /**
     * Seuils effectifs : défauts surchargés par les valeurs enregistrées valides.
     *
     * @param array<string, mixed> $stored clé => valeur enregistrée
     *
     * @return array<string, int>
     */
    public static function merge(array $stored): array
    {
        $out = self::defaults();

        foreach ($stored as $key => $value) {
            $clean = self::sanitizeValue((string) $key, $value);

            if ($clean !== null) {
                $out[(string) $key] = $clean;
            }
        }

        return $out;
    }

    /** Valeur entière valide pour ce seuil, ou null (clé inconnue, texte non numérique, hors bornes). */
    public static function sanitizeValue(string $key, mixed $raw): ?int
    {
        $def = self::DEFINITIONS[$key] ?? null;

        if ($def === null || ! is_scalar($raw) || ! preg_match('/^\d{1,6}$/', trim((string) $raw))) {
            return null;
        }

        $v = (int) trim((string) $raw);

        return $v >= $def['min'] && $v <= $def['max'] ? $v : null;
    }

    /** Seuils d'un rapport donné, pour l'affichage. @return array<string, array<string, mixed>> */
    public static function forReport(string $report): array
    {
        return array_filter(self::DEFINITIONS, fn ($d) => $d['report'] === $report);
    }
}
