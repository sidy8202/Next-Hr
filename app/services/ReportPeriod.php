<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Périodes des rapports : pures (sans base de données), donc testables seules. */
final class ReportPeriod
{
    public const PRESETS = [
        'month' => 'Ce mois-ci', 'last_month' => 'Mois dernier', 'quarter' => 'Ce trimestre', 'year' => 'Cette année',
        '12m' => '12 derniers mois', 'all' => 'Depuis le début', 'custom' => 'Période personnalisée',
    ];

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} [début, fin] inclus. */
    public static function resolve(string $preset, ?string $from = null, ?string $to = null, ?CarbonInterface $now = null): array
    {
        $today = ($now ? CarbonImmutable::parse($now) : CarbonImmutable::today())->startOfDay();

        switch ($preset) {
            case 'month':
                return [$today->startOfMonth(), $today];
            case 'last_month':
                $m = $today->subMonthNoOverflow();

                return [$m->startOfMonth(), $m->endOfMonth()->startOfDay()];
            case 'quarter':
                return [$today->startOfQuarter(), $today];
            case 'year':
                return [$today->startOfYear(), $today];
            case 'all':
                return [CarbonImmutable::create(2000, 1, 1)->startOfDay(), $today];
            case 'custom':
                try {
                    $f = CarbonImmutable::parse((string) $from)->startOfDay();
                    $t = CarbonImmutable::parse((string) $to)->startOfDay();

                    if ($f->lte($t)) {
                        return [$f, $t];
                    }
                } catch (\Throwable) {
                    // dates invalides : on retombe sur 12 mois
                }
                // no break
            default:
                return [$today->subMonthsNoOverflow(11)->startOfMonth(), $today];
        }
    }

    /** Période de comparaison : « previous » = même durée juste avant ; « year » = même période un an plus tôt ; sinon null. */
    public static function previous(CarbonImmutable $from, CarbonImmutable $to, string $mode): ?array
    {
        if ($mode === 'year') {
            return [$from->subYear(), $to->subYear()];
        }

        if ($mode === 'previous') {
            $days = (int) $from->diffInDays($to) + 1;
            $pTo = $from->subDay();

            return [$pTo->subDays($days - 1), $pTo];
        }

        return null;
    }

    /** @return CarbonImmutable[] premiers jours de chaque mois couvert par la période. */
    public static function months(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $out = [];

        for ($m = $from->startOfMonth(); $m->lte($to); $m = $m->addMonthNoOverflow()) {
            $out[] = $m;
        }

        return $out;
    }

    public static function delta(float|int $current, float|int $previous): ?int
    {
        return $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null;
    }

    public static function sizeBracket(int $employees): string
    {
        return match (true) {
            $employees <= 25 => '0-25',
            $employees <= 55 => '26-55',
            $employees <= 100 => '56-100',
            $employees <= 250 => '101-250',
            default => '251+',
        };
    }
}
