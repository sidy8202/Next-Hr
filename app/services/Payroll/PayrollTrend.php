<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR de la courbe sur 12 mois et des comparaisons (mois précédent, même mois de l'an dernier).
 * Aucune base de données : on reçoit les totaux de chaque mois déjà lus.
 *
 * Forme d'entrée « mois » : période AAAA-MM => ['status' => 'calculated' | 'closed', 'totals' => résultat de PayrollDashboardStats::totals()].
 * Un mois absent de la liste (non calculé) crée un TROU dans la courbe : on ne devine jamais une valeur.
 */
final class PayrollTrend
{
    /** Indicateurs de la courbe : clé de totals() => libellé. */
    public const METRICS = [
        'net_to_pay' => 'Net à payer',
        'gross' => 'Masse brute',
        'employer_cost' => 'Coût employeur',
    ];

    public const WINDOW = 12;

    private const SHORT = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    /** Décale une période AAAA-MM de $n mois (négatif = passé), en arithmétique entière. */
    public static function shift(string $period, int $n): string
    {
        $index = (int) substr($period, 0, 4) * 12 + ((int) substr($period, 5, 2) - 1) + $n;

        return sprintf('%04d-%02d', intdiv($index, 12), $index % 12 + 1);
    }

    /** @return array<int, string> $count périodes, de la plus ancienne à $end (incluse). */
    public static function periods(string $end, int $count = self::WINDOW): array
    {
        $out = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $out[] = self::shift($end, -$i);
        }

        return $out;
    }

    /**
     * Valeurs d'un indicateur sur les 12 mois qui se terminent à $end.
     *
     * @param array<string, array{status: string, totals: array<string, int>}> $months
     *
     * @return array<int, array{period: string, value: ?int, provisional: bool}>
     */
    public static function series(array $months, string $end, string $metric): array
    {
        if (! array_key_exists($metric, self::METRICS)) {
            $metric = 'net_to_pay';
        }

        $out = [];

        foreach (self::periods($end) as $period) {
            $m = $months[$period] ?? null;
            $out[] = [
                'period' => $period,
                'value' => $m === null ? null : (int) ($m['totals'][$metric] ?? 0),
                'provisional' => $m !== null && ($m['status'] ?? '') === 'calculated',
            ];
        }

        return $out;
    }

    /**
     * Repères chiffrés de la série : nombre de mois calculés, plus haut, plus bas, moyenne (demi-unité supérieure).
     * Aucune interprétation (pas de « saisonnalité » : douze mois de recul ne la prouvent pas).
     *
     * @param array<int, array{period: string, value: ?int, provisional: bool}> $series
     *
     * @return array{count: int, min: ?array{period: string, value: int}, max: ?array{period: string, value: int}, average: ?int}
     */
    public static function stats(array $series): array
    {
        $min = null;
        $max = null;
        $sum = 0;
        $count = 0;

        foreach ($series as $p) {
            if ($p['value'] === null) {
                continue;
            }

            $count++;
            $sum += $p['value'];

            if ($min === null || $p['value'] < $min['value']) {
                $min = ['period' => $p['period'], 'value' => $p['value']];
            }

            if ($max === null || $p['value'] > $max['value']) {
                $max = ['period' => $p['period'], 'value' => $p['value']];
            }
        }

        return ['count' => $count, 'min' => $min, 'max' => $max, 'average' => $count === 0 ? null : intdiv($sum * 2 + $count, 2 * $count)];
    }

    /**
     * Comparaison de chaque indicateur du mois affiché avec le mois précédent et le même mois de l'an dernier.
     * Les textes sont déjà formatés : la vue n'a plus rien à calculer.
     *
     * @param array<string, array{status: string, totals: array<string, int>}> $months
     *
     * @return array<int, array<string, mixed>> vide si le mois affiché n'a pas de chiffres
     */
    public static function comparison(array $months, string $current): array
    {
        $now = $months[$current] ?? null;

        if ($now === null) {
            return [];
        }

        $refs = ['previous' => self::shift($current, -1), 'last_year' => self::shift($current, -12)];
        $rows = [];

        foreach (PayrollDashboardStats::METRICS as $key => $label) {
            $row = ['key' => $key, 'label' => $label, 'current' => PayrollDashboardStats::number((int) $now['totals'][$key])];

            foreach ($refs as $name => $period) {
                $ref = $months[$period] ?? null;
                $delta = $ref === null ? null : PayrollDashboardStats::delta((int) $now['totals'][$key], (int) $ref['totals'][$key]);

                $row[$name] = [
                    'period' => $period,
                    'value' => $ref === null ? null : PayrollDashboardStats::number((int) $ref['totals'][$key]),
                    'delta' => PayrollDashboardStats::deltaShort($delta),
                    'direction' => $delta['direction'] ?? 'none',
                    'provisional' => $ref !== null && ($ref['status'] ?? '') === 'calculated',
                ];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Géométrie de la courbe SVG (viewBox largeur × hauteur). L'axe part toujours de zéro.
     *
     * @param array<int, array{period: string, value: ?int, provisional: bool}> $series
     *
     * @return array<string, mixed>
     */
    public static function chart(array $series, int $width = 640, int $height = 260): array
    {
        $left = 56;
        $right = 16;
        $top = 16;
        $bottom = 34;
        $plotW = $width - $left - $right;
        $plotH = $height - $top - $bottom;
        $n = count($series);

        $max = 0;

        foreach ($series as $p) {
            $max = max($max, (int) $p['value']);
        }

        $step = self::niceStep($max);
        $axisMax = $step * 4;

        $ticks = [];

        for ($i = 0; $i <= 4; $i++) {
            $ticks[] = ['y' => self::f($top + $plotH - $plotH * $i / 4), 'label' => self::compact($step * $i)];
        }

        $points = [];
        $xlabels = [];
        $paths = [];
        $run = [];

        foreach ($series as $i => $p) {
            $x = $n > 1 ? $left + $plotW * $i / ($n - 1) : $left + $plotW / 2;
            $month = (int) substr($p['period'], 5, 2);

            $xlabels[] = [
                'x' => self::f($x),
                'text' => self::SHORT[$month - 1],
                'year' => $i === 0 || $month === 1 ? substr($p['period'], 0, 4) : null,
            ];

            if ($p['value'] === null) {
                $paths[] = $run;
                $run = [];

                continue;
            }

            $y = $top + $plotH - min(max($p['value'], 0), $axisMax) * $plotH / $axisMax;
            $point = ['x' => self::f($x), 'y' => self::f($y), 'value' => $p['value'], 'period' => $p['period'], 'provisional' => $p['provisional']];
            $points[] = $point;
            $run[] = $point;
        }

        $paths[] = $run;

        // Un trait relie seulement des mois consécutifs ; un mois isolé reste un simple point.
        $lines = [];

        foreach ($paths as $r) {
            if (count($r) < 2) {
                continue;
            }

            $d = '';

            foreach ($r as $k => $pt) {
                $d .= ($k === 0 ? 'M' : ' L') . $pt['x'] . ' ' . $pt['y'];
            }

            $lines[] = $d;
        }

        return [
            'width' => $width,
            'height' => $height,
            'plot' => ['left' => $left, 'right' => $width - $right, 'top' => $top, 'bottom' => $top + $plotH],
            'ticks' => $ticks,
            'axis_max' => $axisMax,
            'points' => $points,
            'paths' => $lines,
            'xlabels' => $xlabels,
            'empty' => count($points) < 2,
        ];
    }

    /** Plus petit pas « rond » (1, 2, 2,5, 5, 10 × puissance de dix) dont quatre pas couvrent le maximum. */
    public static function niceStep(int $max): int
    {
        if ($max <= 0) {
            return 1;
        }

        $target = intdiv($max + 3, 4);
        $mag = 1;

        while ($mag * 10 <= $target) {
            $mag *= 10;
        }

        $candidates = [$mag, 2 * $mag];

        if ($mag >= 10) {
            $candidates[] = intdiv(25 * $mag, 10);
        }

        $candidates[] = 5 * $mag;
        $candidates[] = 10 * $mag;

        foreach ($candidates as $c) {
            if ($c >= $target) {
                return $c;
            }
        }

        return 10 * $mag;
    }

    /** Étiquette d'axe courte : 0, 800 k, 2,5 M. */
    public static function compact(int $value): string
    {
        if ($value >= 1000000) {
            $tenths = intdiv($value + 50000, 100000);

            return ($tenths % 10 === 0 ? (string) intdiv($tenths, 10) : intdiv($tenths, 10) . ',' . $tenths % 10) . ' M';
        }

        if ($value >= 1000) {
            return intdiv($value + 500, 1000) . ' k';
        }

        return (string) $value;
    }

    /** Coordonnée SVG : un chiffre après le point, quelle que soit la locale du serveur. */
    protected static function f(float|int $v): string
    {
        return number_format((float) $v, 1, '.', '');
    }
}
