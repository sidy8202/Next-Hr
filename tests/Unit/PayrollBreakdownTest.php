<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollBreakdown;
use PHPUnit\Framework\TestCase;

class PayrollBreakdownTest extends TestCase
{
    private function dept(?string $name, int $count, int $net): array
    {
        return ['name' => $name, 'count' => $count, 'net_to_pay' => $net, 'gross' => $net + 600000, 'employer_cost' => $net + 1100000];
    }

    public function test_departments_sorted_by_value_with_shares(): void
    {
        $r = PayrollBreakdown::byDepartment([$this->dept('Finance', 4, 600000), $this->dept('Logistique', 8, 1400000), $this->dept('RH', 3, 1000000)], 'net_to_pay');

        $this->assertSame(['Logistique', 'RH', 'Finance'], array_column($r['items'], 'name'));
        $this->assertSame(3000000, $r['total']);
        $this->assertSame([47, 33, 20], array_column($r['items'], 'share'));
        $this->assertSame([100, 71, 43], array_column($r['items'], 'bar'));
        $this->assertSame(0, $r['hidden_people']);
    }

    /** Deux petits services (1 + 1 personne) : même regroupés, ils restent sous 3 personnes, donc masqués. */
    public function test_small_groups_below_threshold_are_hidden(): void
    {
        $r = PayrollBreakdown::byDepartment([$this->dept('Finance', 5, 1000000), $this->dept('Direction', 1, 900000), $this->dept('Audit', 1, 800000)], 'net_to_pay');

        $this->assertSame(['Finance'], array_column($r['items'], 'name'));
        $this->assertSame(2, $r['hidden_people']);
        $this->assertSame(2, $r['hidden_groups']);
        $this->assertSame(2700000, $r['total']);
        $this->assertSame(37, $r['items'][0]['share']);
    }

    /** Trois petits services de 1 personne : regroupés ils atteignent 3 et deviennent « Autres services ». */
    public function test_small_groups_are_merged_when_together_large_enough(): void
    {
        $r = PayrollBreakdown::byDepartment([$this->dept('Finance', 5, 1000000), $this->dept('Direction', 1, 900000), $this->dept('Audit', 1, 800000), $this->dept('Juridique', 1, 700000)], 'net_to_pay');

        $this->assertSame(['Autres services', 'Finance'], array_column($r['items'], 'name'));
        $this->assertSame(3, $r['items'][0]['count']);
        $this->assertSame(2400000, $r['items'][0]['value']);
        $this->assertSame(0, $r['hidden_people']);
    }

    public function test_unnamed_departments_are_grouped_together(): void
    {
        $r = PayrollBreakdown::byDepartment([$this->dept(null, 2, 500000), $this->dept('  ', 1, 400000), $this->dept('Finance', 4, 900000)], 'net_to_pay');

        $this->assertSame(['Finance', 'Sans service'], array_column($r['items'], 'name'));
        $this->assertSame(3, $r['items'][1]['count']);
    }

    public function test_metric_choice_and_unknown_metric(): void
    {
        $rows = [$this->dept('A', 3, 100), $this->dept('B', 3, 200)];

        $this->assertSame(1200300, PayrollBreakdown::byDepartment($rows, 'gross')['total']);
        $this->assertSame(300, PayrollBreakdown::byDepartment($rows, 'inconnu')['total']);
        $this->assertSame([], PayrollBreakdown::byDepartment([], 'net_to_pay')['items']);
    }

    public function test_equal_values_are_ordered_by_name(): void
    {
        $r = PayrollBreakdown::byDepartment([$this->dept('Zèbre', 3, 100), $this->dept('Alpha', 3, 100)], 'net_to_pay');

        $this->assertSame(['Alpha', 'Zèbre'], array_column($r['items'], 'name'));
    }

    /** Brut 3 300 000 = net 2 640 000 + total salarié 500 000 (dont ITS 310 000) + autres retenues 160 000. */
    public function test_gross_to_net_adds_up_to_the_gross(): void
    {
        $items = PayrollBreakdown::grossToNet(['gross' => 3300000, 'net_to_pay' => 2640000, 'deductions' => 500000], 310000);

        $this->assertSame(['net', 'social', 'its', 'other'], array_column($items, 'key'));
        $this->assertSame([2640000, 190000, 310000, 160000], array_column($items, 'amount'));
        $this->assertSame(3300000, array_sum(array_column($items, 'amount')));
        $this->assertSame([80, 6, 9, 5], array_column($items, 'share'));
    }

    public function test_gross_to_net_with_zero_gross(): void
    {
        $items = PayrollBreakdown::grossToNet(['gross' => 0, 'net_to_pay' => 0, 'deductions' => 0], 0);

        $this->assertSame([0, 0, 0, 0], array_column($items, 'share'));
    }

    public function test_composition_groups_the_smallest_lines(): void
    {
        $lines = [
            ['key' => 'earning:100001', 'label' => 'Salaire de base', 'kind' => 'earning', 'amount' => 2000000],
            ['key' => 'earning:100002', 'label' => 'Sursalaire', 'kind' => 'earning', 'amount' => 600000],
            ['key' => 'earning:P1', 'label' => 'Prime de transport', 'kind' => 'earning', 'amount' => 300000],
            ['key' => 'earning:P2', 'label' => 'Prime de logement', 'kind' => 'earning', 'amount' => 250000],
            ['key' => 'earning:P3', 'label' => 'Heures supplémentaires', 'kind' => 'earning', 'amount' => 100000],
            ['key' => 'earning:P4', 'label' => 'Prime de panier', 'kind' => 'earning', 'amount' => 60000],
            ['key' => 'earning:P5', 'label' => 'Prime de risque', 'kind' => 'earning', 'amount' => 40000],
            ['key' => 'deduction:ABS', 'label' => 'Absences', 'kind' => 'deduction', 'amount' => 50000],
        ];
        $c = PayrollBreakdown::composition($lines, 3300000, 5);

        $this->assertSame(['Salaire de base', 'Sursalaire', 'Prime de transport', 'Prime de logement', 'Heures supplémentaires', 'Autres rubriques (2)'], array_column($c['items'], 'label'));
        $this->assertSame(100000, $c['items'][5]['amount']);
        $this->assertSame(3350000, $c['earnings_total']);
        $this->assertSame(50000, $c['deductions']);
        $this->assertSame(0, $c['gap']);
        $this->assertTrue($c['consistent']);
        $this->assertSame([60, 18, 9, 7, 3, 3], array_column($c['items'], 'share'));
    }

    public function test_composition_reports_a_gap_instead_of_hiding_it(): void
    {
        $c = PayrollBreakdown::composition([['key' => 'earning:100001', 'label' => 'Salaire de base', 'kind' => 'earning', 'amount' => 900000]], 1000000);

        $this->assertFalse($c['consistent']);
        $this->assertSame(-100000, $c['gap']);
    }

    public function test_composition_without_lines(): void
    {
        $c = PayrollBreakdown::composition([], 0);

        $this->assertSame([], $c['items']);
        $this->assertTrue($c['consistent']);
    }
}
