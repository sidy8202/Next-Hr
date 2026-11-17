<?php

namespace Tests\Unit;

use App\Services\Reports\PayrollCostReport;
use App\Services\Reports\ReportThresholds;
use PHPUnit\Framework\TestCase;

class PayrollCostReportTest extends TestCase
{
    private function month(int $n, int $gross, int $net, int $cost, int $employer): array
    {
        return ['n' => $n, 'gross' => $gross, 'net_to_pay' => $net, 'employer_cost' => $cost, 'employer_total' => $employer];
    }

    private function cur(): array
    {
        return PayrollCostReport::aggregate(['2026-09' => $this->month(10, 3000000, 2400000, 3500000, 500000), '2026-10' => $this->month(11, 3300000, 2640000, 3850000, 550000)]);
    }

    private function prev(): array
    {
        return PayrollCostReport::aggregate(['2025-09' => $this->month(10, 3000000, 2400000, 3500000, 500000), '2025-10' => $this->month(10, 3000000, 2400000, 3500000, 500000)]);
    }

    private function item(array $kpis, string $key): array
    {
        return array_values(array_filter($kpis['items'], fn ($i) => $i['key'] === $key))[0];
    }

    public function test_aggregate_sums_the_months(): void
    {
        $a = $this->cur();

        $this->assertSame(['months' => 2, 'n_sum' => 21, 'gross' => 6300000, 'net_to_pay' => 5040000, 'employer_cost' => 7350000, 'employer_total' => 1050000], $a);
        $this->assertSame(0, PayrollCostReport::aggregate([])['months']);
    }

    public function test_kpis_compare_totals_and_averages_when_months_match(): void
    {
        $k = PayrollCostReport::kpis($this->cur(), $this->prev());

        $this->assertNull($k['note']);
        $this->assertSame(5, $this->item($k, 'employer_cost')['delta']);
        $this->assertSame(5, $this->item($k, 'gross')['delta']);
        $this->assertSame(5, $this->item($k, 'net_to_pay')['delta']);
        $this->assertSame(11, $this->item($k, 'headcount')['value']);
        $this->assertSame(10, $this->item($k, 'headcount')['delta']);
        $this->assertSame(350000, $this->item($k, 'cost_per_head')['value']);
        $this->assertSame(0, $this->item($k, 'cost_per_head')['delta']);
        $this->assertSame(7350000, $this->item($k, 'employer_cost')['value']);
    }

    /** Deux mois contre un seul : les totaux ne sont pas comparés (écart trompeur), les moyennes le sont. */
    public function test_totals_are_not_compared_when_month_counts_differ(): void
    {
        $prev = PayrollCostReport::aggregate(['2025-10' => $this->month(10, 3000000, 2400000, 3500000, 500000)]);
        $k = PayrollCostReport::kpis($this->cur(), $prev);

        $this->assertSame('La comparaison porte sur 2 mois calculés contre 1 : les écarts des totaux ne sont pas calculés.', $k['note']);
        $this->assertNull($this->item($k, 'employer_cost')['delta']);
        $this->assertNull($this->item($k, 'gross')['delta']);
        $this->assertSame(10, $this->item($k, 'headcount')['delta']);
        $this->assertSame(0, $this->item($k, 'cost_per_head')['delta']);
    }

    public function test_kpis_without_comparison_or_without_data(): void
    {
        $k = PayrollCostReport::kpis($this->cur(), null);

        $this->assertNull($k['note']);
        $this->assertNull($this->item($k, 'employer_cost')['delta']);
        $this->assertCount(5, $k['items']);

        $empty = PayrollCostReport::kpis(PayrollCostReport::aggregate([]), $this->prev());
        $this->assertSame([], $empty['items']);

        $noMonths = PayrollCostReport::kpis($this->cur(), PayrollCostReport::aggregate([]));
        $this->assertNull($noMonths['note']);
        $this->assertNull($this->item($noMonths, 'employer_cost')['delta']);
    }

    /** Coût 300 000 → 330 000 : +10 % = 40,0 points d'embauche, −33,3 de départ, +3,3 des personnes restées. */
    public function test_decomposition_of_the_cost_change(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000, 2 => 100000, 3 => 100000], [1 => 110000, 2 => 100000, 4 => 120000]);

        $this->assertSame(300000, $d['ref_total']);
        $this->assertSame(330000, $d['cur_total']);
        $this->assertSame(30000, $d['delta']);
        $this->assertSame(10.0, $d['pct']);
        $this->assertSame(['count' => 1, 'amount' => 120000, 'points' => 40.0], $d['new']);
        $this->assertSame(['count' => 1, 'amount' => -100000, 'points' => -33.3], $d['left']);
        $this->assertSame(['count' => 2, 'amount' => 10000, 'points' => 3.3], $d['stay']);
        $this->assertEqualsWithDelta($d['pct'], $d['new']['points'] + $d['left']['points'] + $d['stay']['points'], 0.0001);
    }

    public function test_decomposition_needs_a_reference_cost(): void
    {
        $this->assertNull(PayrollCostReport::decompose([], [1 => 100000]));
        $this->assertNull(PayrollCostReport::decompose([1 => 0], [1 => 100000]));
    }

    private function input(?array $decomposition, array $over = []): array
    {
        return $over + [
            'ref_month' => '2026-08', 'cur_month' => '2026-11', 'months_back' => 3, 'decomposition' => $decomposition,
            'departments' => ['items' => [['name' => 'Logistique', 'share' => 55], ['name' => 'RH', 'share' => 45]]],
            'cost_per_head' => 350000, 'cost_per_head_prev' => 340000, 'headcount_ref' => 10, 'headcount_cur' => 12, 'metric' => 'employer_cost',
        ];
    }

    public function test_all_sentences_for_a_notable_increase(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000, 2 => 100000, 3 => 100000], [1 => 110000, 2 => 100000, 4 => 120000]);
        $s = PayrollCostReport::sentences($this->input($d), ReportThresholds::defaults());

        $this->assertCount(4, $s);
        $this->assertSame('Le coût employeur a augmenté de 10,0 % en 3 mois (de 300 000 à 330 000 FCFA) : +40,0 points par les nouvelles embauches (1 personne), −33,3 points par les départs (1 personne) et +3,3 points par l\'évolution des salaires et rubriques des personnes présentes aux deux mois.', $s[0]['text']);
        $this->assertSame('Le service « Logistique » concentre 55 % du coût employeur.', $s[1]['text']);
        $this->assertSame('Le coût employeur moyen par salarié est de 350 000 FCFA par mois (340 000 FCFA sur la période de comparaison).', $s[2]['text']);
        $this->assertSame('L\'effectif payé passe de 10 à 12 bulletins entre 08/2026 et 11/2026.', $s[3]['text']);
    }

    /** Chaque phrase affiche le seuil qui l'a produite : elle est vérifiable. */
    public function test_each_sentence_carries_its_rule_and_threshold(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000], [1 => 120000]);
        $s = PayrollCostReport::sentences($this->input($d), ['cost_change_notice_percent' => 8, 'cost_trend_months' => 3, 'dept_concentration_percent' => 50, 'grid_gap_alert_percent' => 10]);

        $this->assertStringContainsString('à partir de 8 %', $s[0]['rule']);
        $this->assertStringContainsString('3 mois', $s[0]['rule']);
        $this->assertStringContainsString('à partir de 50 %', $s[1]['rule']);

        foreach ($s as $line) {
            $this->assertNotSame('', $line['rule']);
        }
    }

    public function test_a_small_change_is_called_stable(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000], [1 => 102000]);
        $s = PayrollCostReport::sentences($this->input($d), ReportThresholds::defaults());

        $this->assertSame('Le coût employeur est stable entre 08/2026 et 11/2026 (+2,0 %).', $s[0]['text']);
    }

    public function test_the_notice_threshold_is_respected(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000], [1 => 102000]);
        $s = PayrollCostReport::sentences($this->input($d), ['cost_change_notice_percent' => 1] + ReportThresholds::defaults());

        $this->assertSame('Le coût employeur a augmenté de 2,0 % en 3 mois (de 100 000 à 102 000 FCFA) : +2,0 points par l\'évolution des salaires et rubriques des personnes présentes aux deux mois.', $s[0]['text']);
    }

    public function test_a_decrease_by_departures(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000, 2 => 100000], [1 => 100000]);
        $s = PayrollCostReport::sentences($this->input($d), ReportThresholds::defaults());

        $this->assertSame('Le coût employeur a baissé de 50,0 % en 3 mois (de 200 000 à 100 000 FCFA) : −50,0 points par les départs (1 personne).', $s[0]['text']);
    }

    public function test_missing_reference_month_is_said_plainly(): void
    {
        $s = PayrollCostReport::sentences($this->input(null), ReportThresholds::defaults());

        $this->assertSame('Pas de lecture de l\'évolution : aucun bulletin calculé pour 08/2026 (3 mois avant 11/2026).', $s[0]['text']);
        $this->assertSame([], PayrollCostReport::sentences($this->input(null, ['cur_month' => null]), ReportThresholds::defaults()));
    }

    public function test_concentration_needs_two_visible_services_above_the_threshold(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000], [1 => 102000]);
        $thr = ReportThresholds::defaults();

        $single = PayrollCostReport::sentences($this->input($d, ['departments' => ['items' => [['name' => 'Logistique', 'share' => 100]]]]), $thr);
        $low = PayrollCostReport::sentences($this->input($d, ['departments' => ['items' => [['name' => 'A', 'share' => 35], ['name' => 'B', 'share' => 30]]]]), $thr);
        $others = PayrollCostReport::sentences($this->input($d, ['departments' => ['items' => [['name' => 'Autres services', 'share' => 60], ['name' => 'A', 'share' => 40]]]]), $thr);

        foreach ([$single, $low, $others] as $s) {
            foreach ($s as $line) {
                $this->assertStringNotContainsString('concentre', $line['text']);
            }
        }
    }

    public function test_the_concentration_sentence_names_the_chosen_indicator(): void
    {
        $d = PayrollCostReport::decompose([1 => 100000], [1 => 102000]);
        $s = PayrollCostReport::sentences($this->input($d, ['metric' => 'net_to_pay']), ReportThresholds::defaults());

        $this->assertSame('Le service « Logistique » concentre 55 % du net à payer.', $s[1]['text']);
    }
}
