<?php

namespace App\Services\Payroll;

/**
 * Calcul PUR des répartitions du tableau de bord paie : par service, du brut au net, composition du brut.
 * Aucune base de données.
 *
 * Confidentialité : un service de moins de MIN_GROUP personnes n'est jamais affiché seul (son total révélerait un salaire).
 */
final class PayrollBreakdown
{
    /** Effectif minimal d'un groupe affiché (même garde-fou que les rapports de rémunération). */
    public const MIN_GROUP = 3;

    public const NO_DEPARTMENT = 'Sans service';

    public const OTHER_DEPARTMENTS = 'Autres services';

    /**
     * Répartition d'un indicateur par service.
     *
     * @param array<int, array{name: ?string, count: int, net_to_pay: int, gross: int, employer_cost: int}> $rows une ligne par service
     *
     * @return array{items: array<int, array{name: string, count: int, value: int, share: int, bar: int}>, total: int, hidden_people: int, hidden_groups: int}
     */
    public static function byDepartment(array $rows, string $metric): array
    {
        if (! array_key_exists($metric, PayrollTrend::METRICS)) {
            $metric = 'net_to_pay';
        }

        $groups = [];

        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? '')) === '' ? self::NO_DEPARTMENT : trim((string) $r['name']);
            $groups[$name]['count'] = ($groups[$name]['count'] ?? 0) + (int) $r['count'];
            $groups[$name]['value'] = ($groups[$name]['value'] ?? 0) + (int) $r[$metric];
        }

        $total = (int) array_sum(array_column($groups, 'value'));
        $shown = [];
        $smallCount = 0;
        $smallValue = 0;
        $smallGroups = 0;

        foreach ($groups as $name => $g) {
            if ($g['count'] >= self::MIN_GROUP) {
                $shown[] = ['name' => (string) $name, 'count' => $g['count'], 'value' => $g['value']];
            } else {
                $smallCount += $g['count'];
                $smallValue += $g['value'];
                $smallGroups++;
            }
        }

        $hiddenPeople = 0;
        $hiddenGroups = 0;

        if ($smallGroups > 0) {
            if ($smallCount >= self::MIN_GROUP) {
                $shown[] = ['name' => self::OTHER_DEPARTMENTS, 'count' => $smallCount, 'value' => $smallValue];
            } else {
                $hiddenPeople = $smallCount;
                $hiddenGroups = $smallGroups;
            }
        }

        usort($shown, fn ($a, $b) => [$b['value'], $a['name']] <=> [$a['value'], $b['name']]);

        $maxValue = $shown === [] ? 0 : max(array_column($shown, 'value'));

        foreach ($shown as $i => $s) {
            $shown[$i]['share'] = $total > 0 ? intdiv(max(0, $s['value']) * 200 + $total, 2 * $total) : 0;
            $shown[$i]['bar'] = $maxValue > 0 ? intdiv(max(0, $s['value']) * 200 + $maxValue, 2 * $maxValue) : 0;
        }

        return ['items' => $shown, 'total' => $total, 'hidden_people' => $hiddenPeople, 'hidden_groups' => $hiddenGroups];
    }

    /**
     * Du brut au net : où va le brut. L'identité du moteur est net à payer = brut − total salarié − autres retenues,
     * donc « autres retenues » se déduit sans lire les bulletins : prêts, avantages en nature, retenues internes et individuelles.
     *
     * @param array{gross: int, net_to_pay: int, deductions: int} $totals résultat de PayrollDashboardStats::totals()
     * @param int $its total de l'impôt sur salaire (déjà compris dans « deductions »)
     *
     * @return array<int, array{key: string, label: string, amount: int, share: int}>
     */
    public static function grossToNet(array $totals, int $its): array
    {
        $gross = (int) $totals['gross'];
        $social = max(0, (int) $totals['deductions'] - $its);
        $other = $gross - (int) $totals['deductions'] - (int) $totals['net_to_pay'];

        $items = [
            ['key' => 'net', 'label' => 'Net à payer', 'amount' => (int) $totals['net_to_pay']],
            ['key' => 'social', 'label' => 'Cotisations sociales (part salarié)', 'amount' => $social],
            ['key' => 'its', 'label' => 'Impôt sur salaire (ITS)', 'amount' => $its],
            ['key' => 'other', 'label' => 'Autres retenues sur le net (prêts, avantages en nature, retenues)', 'amount' => $other],
        ];

        foreach ($items as $i => $it) {
            $items[$i]['share'] = $gross > 0 ? intdiv(max(0, $it['amount']) * 200 + $gross, 2 * $gross) : 0;
        }

        return $items;
    }

    /**
     * Composition du brut par rubrique : les plus grosses rubriques, le reste regroupé, et les retenues sur le brut à part.
     * Vérifie que la somme retombe sur le brut des bulletins : sinon l'écart est annoncé, jamais caché.
     *
     * @param array<int, array{key: string, label: string, kind: string, amount: int}> $lines kind = earning | deduction ; une ligne par rubrique du mois
     *
     * @return array{items: array<int, array{label: string, amount: int, share: int}>, deductions: int, earnings_total: int, gross: int, gap: int, consistent: bool}
     */
    public static function composition(array $lines, int $gross, int $top = 5): array
    {
        $earnings = [];
        $deductions = 0;

        foreach ($lines as $l) {
            if (($l['kind'] ?? 'earning') === 'deduction') {
                $deductions += (int) $l['amount'];
            } else {
                $earnings[] = ['label' => (string) $l['label'], 'amount' => (int) $l['amount']];
            }
        }

        usort($earnings, fn ($a, $b) => [$b['amount'], $a['label']] <=> [$a['amount'], $b['label']]);

        $earningsTotal = (int) array_sum(array_column($earnings, 'amount'));
        $items = array_slice($earnings, 0, $top);
        $rest = array_slice($earnings, $top);

        if ($rest !== []) {
            $items[] = ['label' => 'Autres rubriques (' . count($rest) . ')', 'amount' => (int) array_sum(array_column($rest, 'amount'))];
        }

        foreach ($items as $i => $it) {
            $items[$i]['share'] = $earningsTotal > 0 ? intdiv(max(0, $it['amount']) * 200 + $earningsTotal, 2 * $earningsTotal) : 0;
        }

        $gap = $earningsTotal - $deductions - $gross;

        return ['items' => $items, 'deductions' => $deductions, 'earnings_total' => $earningsTotal, 'gross' => $gross, 'gap' => $gap, 'consistent' => $gap === 0];
    }
}
