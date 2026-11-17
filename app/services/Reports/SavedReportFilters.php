<?php

namespace App\Services\Reports;

use App\Services\ReportPeriod;

/**
 * Nettoie les critères d'un rapport enregistré avant de les appliquer : liste blanche de clés, types contrôlés.
 * Ce qui est stocké en base ou vient du navigateur n'est jamais appliqué tel quel. Fonction pure.
 */
final class SavedReportFilters
{
    private const METRICS = ['net_to_pay', 'gross', 'employer_cost'];

    /**
     * @param array<string, mixed> $raw
     *
     * @return array{preset: string, from: string, to: string, compare: string, depts: array<int, int>, positions: array<int, int>, contracts: array<int, string>, genders: array<int, string>, categories: array<int, int>, metric: string}
     */
    public static function sanitize(array $raw): array
    {
        $preset = (string) ($raw['preset'] ?? '12m');
        $compare = (string) ($raw['compare'] ?? 'previous');
        $metric = (string) ($raw['metric'] ?? 'employer_cost');

        return [
            'preset' => array_key_exists($preset, ReportPeriod::PRESETS) ? $preset : '12m',
            'from' => self::date($raw['from'] ?? ''),
            'to' => self::date($raw['to'] ?? ''),
            'compare' => in_array($compare, ['none', 'previous', 'year'], true) ? $compare : 'previous',
            'depts' => self::ids($raw['depts'] ?? []),
            'positions' => self::ids($raw['positions'] ?? []),
            'contracts' => self::words($raw['contracts'] ?? []),
            'genders' => array_values(array_unique(array_filter(self::strings($raw['genders'] ?? []), fn ($g) => in_array($g, ['F', 'M', 'N'], true)))),
            'categories' => self::ids($raw['categories'] ?? []),
            'metric' => in_array($metric, self::METRICS, true) ? $metric : 'employer_cost',
        ];
    }

    private static function date(mixed $v): string
    {
        $s = is_string($v) ? trim($v) : '';

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return '';
        }

        return $s;
    }

    /** @return array<int, int> */
    private static function ids(mixed $v): array
    {
        $out = [];

        foreach (is_array($v) ? $v : [] as $x) {
            if ((is_int($x) || (is_string($x) && ctype_digit($x))) && (int) $x > 0) {
                $out[(int) $x] = (int) $x;
            }
        }

        return array_slice(array_values($out), 0, 50);
    }

    /** @return array<int, string> */
    private static function strings(mixed $v): array
    {
        return array_values(array_filter(is_array($v) ? $v : [], 'is_string'));
    }

    /** @return array<int, string> types de contrat : majuscules, lettres, chiffres, espace, tiret, soulignement */
    private static function words(mixed $v): array
    {
        $out = [];

        foreach (self::strings($v) as $s) {
            $w = strtoupper(trim($s));

            if ($w !== '' && strlen($w) <= 30 && preg_match('/^[A-Z0-9 _-]+$/', $w)) {
                $out[$w] = $w;
            }
        }

        return array_slice(array_values($out), 0, 20);
    }
}
