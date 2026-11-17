<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR des chiffres du tableau de bord paie (aucune base de données, testable sans Laravel).
 *
 * Les sommes viennent de la table des bulletins (colonnes déjà agrégées par le calcul de paie) : on ne relit pas les snapshots.
 * Les écarts avec le mois précédent sont des montants entiers en FCFA ; le pourcentage a une décimale, arrondie à la demi-unité supérieure.
 */
final class PayrollDashboardStats
{
    /** Indicateurs affichés : clé => libellé. */
    public const METRICS = [
        'gross' => 'Masse salariale brute',
        'net_to_pay' => 'Net à payer',
        'deductions' => 'Cotisations et impôt retenus',
        'employer_total' => 'Charges patronales',
        'employer_cost' => 'Coût employeur',
    ];

    /**
     * Normalise les sommes brutes d'un mois.
     *
     * @param array<string, mixed> $sums count, gross, net_to_pay, employee_contributions (ITS compris), its, employer_total, employer_cost
     *
     * @return array{count: int, gross: int, net_to_pay: int, deductions: int, employer_total: int, employer_cost: int}
     */
    public static function totals(array $sums): array
    {
        return [
            'count' => max(0, (int) ($sums['count'] ?? 0)),
            'gross' => (int) ($sums['gross'] ?? 0),
            'net_to_pay' => (int) ($sums['net_to_pay'] ?? 0),
            // « employee_contributions » est le total salarié du moteur : cotisations sociales ET impôt sur salaire (l'ITS y est déjà ajouté).
            // On ne rajoute donc pas « its » : ce serait le compter deux fois. Les autres retenues (prêts, avantages en nature) n'y sont pas.
            'deductions' => (int) ($sums['employee_contributions'] ?? 0),
            'employer_total' => (int) ($sums['employer_total'] ?? 0),
            'employer_cost' => (int) ($sums['employer_cost'] ?? 0),
        ];
    }

    /**
     * Écart entre le mois affiché et le mois précédent.
     *
     * @return ?array{diff: int, percent: ?float, direction: string} null si le mois précédent n'a pas de chiffres ;
     *                                                               percent est null si la base de comparaison vaut 0
     */
    public static function delta(int $current, ?int $previous): ?array
    {
        if ($previous === null) {
            return null;
        }

        $diff = $current - $previous;
        $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');

        if ($previous === 0) {
            return ['diff' => $diff, 'percent' => null, 'direction' => $direction];
        }

        $base = abs($previous);
        // Dixièmes de pour cent, arrondis à la demi-unité supérieure, en arithmétique entière (pas d'erreur de virgule flottante).
        $tenths = intdiv(abs($diff) * 2000 + $base, 2 * $base);
        $percent = (float) (($diff < 0 ? -1 : 1) * $tenths) / 10;

        return ['diff' => $diff, 'percent' => $percent, 'direction' => $direction];
    }

    /**
     * Compare chaque indicateur au mois précédent.
     *
     * @param array<string, int> $current  résultat de totals()
     * @param ?array<string, int> $previous résultat de totals() du mois précédent, ou null
     *
     * @return array<string, ?array{diff: int, percent: ?float, direction: string}>
     */
    public static function compare(array $current, ?array $previous): array
    {
        $out = [];

        foreach (array_keys(self::METRICS) as $key) {
            $out[$key] = self::delta((int) $current[$key], $previous === null ? null : (int) $previous[$key]);
        }

        return $out;
    }

    /** « 2 640 000 » */
    public static function number(int $value): string
    {
        return number_format(abs($value), 0, ',', ' ');
    }

    /** « +140 000 » ou « −4 608 » (signe moins typographique) ; zéro sans signe. */
    public static function signed(int $value): string
    {
        if ($value === 0) {
            return '0';
        }

        return ($value > 0 ? '+' : '−') . self::number($value);
    }

    /**
     * Phrase d'écart sous un chiffre.
     *
     * @param ?array{diff: int, percent: ?float, direction: string} $delta
     */
    public static function deltaText(?array $delta): string
    {
        if ($delta === null) {
            return 'Pas de comparaison : le mois précédent n\'a pas de bulletins.';
        }

        if ($delta['direction'] === 'flat') {
            return 'Identique au mois précédent.';
        }

        $arrow = $delta['direction'] === 'up' ? '▲' : '▼';
        $text = $arrow . ' ' . self::signed($delta['diff']) . ' FCFA';

        if ($delta['percent'] !== null) {
            $p = $delta['percent'];
            $text .= ' (' . ($p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 1, ',', ' ') . ' %)';
        }

        return $text . ' par rapport au mois précédent.';
    }

    /**
     * Écart court pour un tableau : « ▲ +140 000 (+5,6 %) », « = » ou « — » (pas de base de comparaison).
     *
     * @param ?array{diff: int, percent: ?float, direction: string} $delta
     */
    public static function deltaShort(?array $delta): string
    {
        if ($delta === null) {
            return '—';
        }

        if ($delta['direction'] === 'flat') {
            return '=';
        }

        $text = ($delta['direction'] === 'up' ? '▲ ' : '▼ ') . self::signed($delta['diff']);

        if ($delta['percent'] !== null) {
            $p = $delta['percent'];
            $text .= ' (' . ($p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 1, ',', ' ') . ' %)';
        }

        return $text;
    }
}
