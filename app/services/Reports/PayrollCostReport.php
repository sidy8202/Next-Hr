<?php

namespace App\Services\Reports;

use App\Services\Payroll\PayrollBreakdown;
use App\Services\Payroll\PayrollDashboardStats;
use App\Services\ReportPeriod;

/**
 * Calculs PURS du rapport « Masse salariale et coût employeur » : totaux d'une période, chiffres clés comparés,
 * décomposition de l'évolution du coût (embauches, départs, personnes restées) et phrases d'interprétation.
 * Les phrases sortent de règles chiffrées (pas d'IA) : chacune porte la règle et le seuil qui l'ont produite.
 * Aucune base de données : les données sont fournies par PayrollReportData.
 */
final class PayrollCostReport
{
    public const METRIC_LABELS = ['employer_cost' => 'coût employeur', 'net_to_pay' => 'net à payer', 'gross' => 'masse brute'];

    /**
     * Totaux d'une période à partir des lignes mensuelles.
     *
     * @param array<string, array{n: int, gross: int, net_to_pay: int, employer_cost: int, employer_total: int}> $monthly
     *
     * @return array{months: int, n_sum: int, gross: int, net_to_pay: int, employer_cost: int, employer_total: int}
     */
    public static function aggregate(array $monthly): array
    {
        $out = ['months' => 0, 'n_sum' => 0, 'gross' => 0, 'net_to_pay' => 0, 'employer_cost' => 0, 'employer_total' => 0];

        foreach ($monthly as $m) {
            $out['months']++;
            $out['n_sum'] += (int) $m['n'];

            foreach (['gross', 'net_to_pay', 'employer_cost', 'employer_total'] as $k) {
                $out[$k] += (int) $m[$k];
            }
        }

        return $out;
    }

    /**
     * Chiffres clés, comparés à la période de comparaison. Les TOTAUX ne sont comparés que si les deux périodes ont le même nombre
     * de mois calculés (sinon l'écart serait trompeur) ; les MOYENNES (effectif payé, coût par salarié) se comparent toujours.
     *
     * @param array{months: int, n_sum: int, gross: int, net_to_pay: int, employer_cost: int, employer_total: int} $cur
     * @param ?array{months: int, n_sum: int, gross: int, net_to_pay: int, employer_cost: int, employer_total: int} $prev
     *
     * @return array{items: array<int, array{key: string, label: string, value: int, suffix: string, delta: ?int, color: string, hint: string}>, note: ?string}
     */
    public static function kpis(array $cur, ?array $prev): array
    {
        if ($cur['months'] === 0) {
            return ['items' => [], 'note' => null];
        }

        $hasPrev = $prev !== null && $prev['months'] > 0;
        $comparable = $hasPrev && $prev['months'] === $cur['months'];
        $note = $hasPrev && ! $comparable
            ? 'La comparaison porte sur ' . $cur['months'] . ' mois calculés contre ' . $prev['months'] . ' : les écarts des totaux ne sont pas calculés.'
            : null;

        $delta = fn (int $c, ?int $p, bool $always): ?int => $hasPrev && $p !== null && ($comparable || $always) ? ReportPeriod::delta($c, $p) : null;

        $head = self::half($cur['n_sum'], $cur['months']);
        $perHead = self::half($cur['employer_cost'], $cur['n_sum']);
        $headPrev = $hasPrev ? self::half($prev['n_sum'], $prev['months']) : null;
        $perHeadPrev = $hasPrev ? self::half($prev['employer_cost'], $prev['n_sum']) : null;

        return [
            'note' => $note,
            'items' => [
                ['key' => 'employer_cost', 'label' => 'Coût employeur', 'value' => $cur['employer_cost'], 'suffix' => ' FCFA', 'delta' => $delta($cur['employer_cost'], $prev['employer_cost'] ?? null, false), 'color' => '#6366f1', 'hint' => $cur['months'] . ' mois calculé(s)'],
                ['key' => 'gross', 'label' => 'Masse brute', 'value' => $cur['gross'], 'suffix' => ' FCFA', 'delta' => $delta($cur['gross'], $prev['gross'] ?? null, false), 'color' => '#0ea5e9', 'hint' => 'brut total des bulletins'],
                ['key' => 'net_to_pay', 'label' => 'Net à payer', 'value' => $cur['net_to_pay'], 'suffix' => ' FCFA', 'delta' => $delta($cur['net_to_pay'], $prev['net_to_pay'] ?? null, false), 'color' => '#10b981', 'hint' => 'net versé aux salariés'],
                ['key' => 'headcount', 'label' => 'Effectif payé moyen', 'value' => $head, 'suffix' => ' pers.', 'delta' => $delta($head, $headPrev, true), 'color' => '#f59e0b', 'hint' => 'bulletins par mois, en moyenne'],
                ['key' => 'cost_per_head', 'label' => 'Coût moyen par salarié', 'value' => $perHead, 'suffix' => ' FCFA', 'delta' => $delta($perHead, $perHeadPrev, true), 'color' => '#8b5cf6', 'hint' => 'coût employeur par bulletin'],
            ],
        ];
    }

    /**
     * Décompose l'évolution du coût entre deux mois : nouvelles embauches, départs, personnes présentes aux deux dates.
     * Les « points » sont des points de pourcentage du coût du mois de référence, à une décimale (demi-unité supérieure).
     *
     * @param array<int, int> $ref employee_id => coût employeur du mois de référence
     * @param array<int, int> $cur employee_id => coût employeur du mois récent
     *
     * @return ?array{ref_total: int, cur_total: int, delta: int, pct: float, new: array{count: int, amount: int, points: float}, left: array{count: int, amount: int, points: float}, stay: array{count: int, amount: int, points: float}}
     *               null si le mois de référence n'a aucun coût
     */
    public static function decompose(array $ref, array $cur): ?array
    {
        $refTotal = (int) array_sum($ref);

        if ($refTotal <= 0) {
            return null;
        }

        $new = ['count' => 0, 'amount' => 0];
        $left = ['count' => 0, 'amount' => 0];
        $stay = ['count' => 0, 'amount' => 0];

        foreach ($cur as $id => $cost) {
            if (! array_key_exists($id, $ref)) {
                $new['count']++;
                $new['amount'] += (int) $cost;
            } else {
                $stay['count']++;
                $stay['amount'] += (int) $cost - (int) $ref[$id];
            }
        }

        foreach ($ref as $id => $cost) {
            if (! array_key_exists($id, $cur)) {
                $left['count']++;
                $left['amount'] -= (int) $cost;
            }
        }

        $curTotal = (int) array_sum($cur);
        $delta = $curTotal - $refTotal;

        return [
            'ref_total' => $refTotal, 'cur_total' => $curTotal, 'delta' => $delta, 'pct' => self::points($delta, $refTotal),
            'new' => $new + ['points' => self::points($new['amount'], $refTotal)],
            'left' => $left + ['points' => self::points($left['amount'], $refTotal)],
            'stay' => $stay + ['points' => self::points($stay['amount'], $refTotal)],
        ];
    }

    /**
     * Phrases d'interprétation. Chaque phrase porte la règle chiffrée et le seuil appliqué, pour être vérifiable.
     *
     * @param array{ref_month: ?string, cur_month: ?string, months_back: int, decomposition: ?array, departments: array{items: array<int, array{name: string, share: int}>}, cost_per_head: ?int, cost_per_head_prev: ?int, headcount_ref: ?int, headcount_cur: ?int, metric: string} $in
     * @param array<string, int> $thresholds résultat de ReportThresholds::merge()
     *
     * @return array<int, array{text: string, rule: string}>
     */
    public static function sentences(array $in, array $thresholds): array
    {
        $out = [];
        $notice = $thresholds['cost_change_notice_percent'];
        $n = (int) $in['months_back'];
        $d = $in['decomposition'];

        if ($in['cur_month'] === null) {
            return [];
        }

        if ($d === null) {
            $out[] = [
                'text' => 'Pas de lecture de l\'évolution : aucun bulletin calculé pour ' . self::label((string) $in['ref_month']) . ' (' . $n . ' mois avant ' . self::label($in['cur_month']) . ').',
                'rule' => 'L\'évolution compare le dernier mois calculé au mois situé ' . $n . ' mois plus tôt.',
            ];
        } else {
            $out[] = ['text' => self::trendText($d, $notice, $n, (string) $in['ref_month'], $in['cur_month']), 'rule' => 'Variation notable à partir de ' . $notice . ' % ; comparaison du dernier mois calculé avec le mois situé ' . $n . ' mois plus tôt.'];
        }

        $items = $in['departments']['items'] ?? [];
        $top = $items[0] ?? null;

        if ($top !== null && count($items) >= 2 && $top['share'] >= $thresholds['dept_concentration_percent'] && $top['name'] !== PayrollBreakdown::OTHER_DEPARTMENTS) {
            $out[] = [
                'text' => 'Le service « ' . $top['name'] . ' » concentre ' . $top['share'] . ' % du ' . (self::METRIC_LABELS[$in['metric']] ?? 'coût employeur') . '.',
                'rule' => 'Concentration signalée à partir de ' . $thresholds['dept_concentration_percent'] . ' % ; services de moins de 3 personnes jamais affichés seuls.',
            ];
        }

        if ($in['cost_per_head'] !== null) {
            $txt = 'Le coût employeur moyen par salarié est de ' . PayrollDashboardStats::number($in['cost_per_head']) . ' FCFA par mois';
            $txt .= $in['cost_per_head_prev'] !== null ? ' (' . PayrollDashboardStats::number($in['cost_per_head_prev']) . ' FCFA sur la période de comparaison).' : '.';
            $out[] = ['text' => $txt, 'rule' => 'Coût employeur total ÷ nombre de bulletins de la période.'];
        }

        if ($in['headcount_ref'] !== null && $in['headcount_cur'] !== null && $in['headcount_ref'] !== $in['headcount_cur']) {
            $out[] = [
                'text' => 'L\'effectif payé passe de ' . $in['headcount_ref'] . ' à ' . $in['headcount_cur'] . ' bulletins entre ' . self::label((string) $in['ref_month']) . ' et ' . self::label($in['cur_month']) . '.',
                'rule' => 'Nombre de bulletins du mois, après filtres.',
            ];
        }

        return $out;
    }

    /** @param array<string, mixed> $d */
    private static function trendText(array $d, int $notice, int $n, string $refMonth, string $curMonth): string
    {
        $pct = (float) $d['pct'];

        if (abs($pct) < $notice) {
            return 'Le coût employeur est stable entre ' . self::label($refMonth) . ' et ' . self::label($curMonth) . ' (' . self::signedPct($pct) . ' %).';
        }

        $parts = [];

        if ($d['new']['count'] > 0) {
            $parts[] = self::pts($d['new']['points']) . ' par les nouvelles embauches (' . $d['new']['count'] . ' personne' . ($d['new']['count'] > 1 ? 's' : '') . ')';
        }

        if ($d['left']['count'] > 0) {
            $parts[] = self::pts($d['left']['points']) . ' par les départs (' . $d['left']['count'] . ' personne' . ($d['left']['count'] > 1 ? 's' : '') . ')';
        }

        if ($d['stay']['amount'] !== 0) {
            $parts[] = self::pts($d['stay']['points']) . ' par l\'évolution des salaires et rubriques des personnes présentes aux deux mois';
        }

        $head = 'Le coût employeur a ' . ($pct > 0 ? 'augmenté' : 'baissé') . ' de ' . number_format(abs($pct), 1, ',', ' ') . ' % en ' . $n . ' mois (de ' . PayrollDashboardStats::number($d['ref_total']) . ' à ' . PayrollDashboardStats::number($d['cur_total']) . ' FCFA)';

        if ($parts === []) {
            return $head . '.';
        }

        $last = array_pop($parts);

        return $head . ' : ' . ($parts === [] ? $last : implode(', ', $parts) . ' et ' . $last) . '.';
    }

    /** Points de pourcentage avec une décimale, demi-unité supérieure, en arithmétique entière. */
    private static function points(int $value, int $base): float
    {
        $tenths = intdiv(abs($value) * 2000 + $base, 2 * $base);

        return (float) (($value < 0 ? -1 : 1) * $tenths) / 10;
    }

    private static function pts(float $p): string
    {
        return ($p < 0 ? '−' : '+') . number_format(abs($p), 1, ',', ' ') . ' point' . (abs($p) >= 2 ? 's' : '');
    }

    private static function signedPct(float $p): string
    {
        return ($p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 1, ',', ' ');
    }

    /** Moyenne entière à la demi-unité supérieure ; 0 si le diviseur est nul. */
    private static function half(int $sum, int $count): int
    {
        return $count > 0 ? intdiv($sum * 2 + $count, 2 * $count) : 0;
    }

    /** « 2026-11 » devient « 11/2026 ». */
    private static function label(string $period): string
    {
        return preg_match('/^(\d{4})-(\d{2})$/', $period, $m) ? $m[2] . '/' . $m[1] : $period;
    }
}
