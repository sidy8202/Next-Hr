<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollDashboardStats;
use App\Services\Payroll\PayrollTrend;
use PHPUnit\Framework\TestCase;

class PayrollTrendTest extends TestCase
{
    private function month(int $net, string $status = 'closed', int $gross = 0): array
    {
        return ['status' => $status, 'totals' => PayrollDashboardStats::totals(['count' => 10, 'gross' => $gross ?: $net + 600000, 'net_to_pay' => $net, 'employee_contributions' => 400000, 'its' => 150000, 'employer_total' => 500000, 'employer_cost' => ($gross ?: $net + 600000) + 500000])];
    }

    public function test_shift_crosses_year_boundaries(): void
    {
        $this->assertSame('2026-10', PayrollTrend::shift('2026-11', -1));
        $this->assertSame('2025-12', PayrollTrend::shift('2026-01', -1));
        $this->assertSame('2025-11', PayrollTrend::shift('2026-11', -12));
        $this->assertSame('2027-01', PayrollTrend::shift('2026-12', 1));
        $this->assertSame('2026-02', PayrollTrend::shift('2026-02', 0));
    }

    public function test_periods_are_twelve_months_ending_at_the_given_one(): void
    {
        $p = PayrollTrend::periods('2026-03');

        $this->assertCount(12, $p);
        $this->assertSame('2025-04', $p[0]);
        $this->assertSame('2026-03', $p[11]);
        $this->assertCount(13, PayrollTrend::periods('2026-03', 13));
        $this->assertSame('2025-03', PayrollTrend::periods('2026-03', 13)[0]);
    }

    /** Un mois non calculé est un trou (null), jamais une valeur devinée ; un mois calculé non clôturé est provisoire. */
    public function test_series_leaves_gaps_and_flags_provisional(): void
    {
        $months = ['2026-09' => $this->month(2500000), '2026-10' => $this->month(2600000), '2026-11' => $this->month(2640000, 'calculated')];
        $s = PayrollTrend::series($months, '2026-11', 'net_to_pay');

        $this->assertCount(12, $s);
        $this->assertNull($s[0]['value']);
        $this->assertSame(2500000, $s[9]['value']);
        $this->assertFalse($s[9]['provisional']);
        $this->assertSame(2640000, $s[11]['value']);
        $this->assertTrue($s[11]['provisional']);
        $this->assertSame('2026-11', $s[11]['period']);
    }

    public function test_series_ignores_unknown_metric(): void
    {
        $s = PayrollTrend::series(['2026-11' => $this->month(1000000)], '2026-11', 'inconnu');

        $this->assertSame(1000000, $s[11]['value']);
    }

    public function test_stats_min_max_average(): void
    {
        $months = ['2026-09' => $this->month(2500000), '2026-10' => $this->month(2600000), '2026-11' => $this->month(2640000)];
        $st = PayrollTrend::stats(PayrollTrend::series($months, '2026-11', 'net_to_pay'));

        $this->assertSame(3, $st['count']);
        $this->assertSame(['period' => '2026-09', 'value' => 2500000], $st['min']);
        $this->assertSame(['period' => '2026-11', 'value' => 2640000], $st['max']);
        $this->assertSame(2580000, $st['average']);
    }

    /** Moyenne à la demi-unité supérieure : (1 + 2) / 2 = 1,5 → 2. */
    public function test_average_rounds_half_up(): void
    {
        $st = PayrollTrend::stats([['period' => '2026-10', 'value' => 1, 'provisional' => false], ['period' => '2026-11', 'value' => 2, 'provisional' => false]]);

        $this->assertSame(2, $st['average']);
    }

    public function test_stats_without_data(): void
    {
        $st = PayrollTrend::stats(PayrollTrend::series([], '2026-11', 'gross'));

        $this->assertSame(['count' => 0, 'min' => null, 'max' => null, 'average' => null], $st);
    }

    public function test_comparison_against_previous_month_and_last_year(): void
    {
        $months = [
            '2025-11' => $this->month(2400000),
            '2026-10' => $this->month(2500000),
            '2026-11' => $this->month(2640000, 'calculated'),
        ];
        $rows = PayrollTrend::comparison($months, '2026-11');

        $this->assertCount(5, $rows);
        $net = array_values(array_filter($rows, fn ($r) => $r['key'] === 'net_to_pay'))[0];

        $this->assertSame('2 640 000', $net['current']);
        $this->assertSame('2026-10', $net['previous']['period']);
        $this->assertSame('2 500 000', $net['previous']['value']);
        $this->assertSame('▲ +140 000 (+5,6 %)', $net['previous']['delta']);
        $this->assertSame('up', $net['previous']['direction']);
        $this->assertFalse($net['previous']['provisional']);
        $this->assertSame('2025-11', $net['last_year']['period']);
        $this->assertSame('▲ +240 000 (+10,0 %)', $net['last_year']['delta']);
    }

    public function test_comparison_missing_reference_month_and_missing_current(): void
    {
        $rows = PayrollTrend::comparison(['2026-11' => $this->month(1000000)], '2026-11');

        $this->assertNull($rows[0]['previous']['value']);
        $this->assertSame('—', $rows[0]['previous']['delta']);
        $this->assertSame('none', $rows[0]['previous']['direction']);
        $this->assertSame([], PayrollTrend::comparison([], '2026-11'));
    }

    public function test_provisional_reference_is_flagged(): void
    {
        $rows = PayrollTrend::comparison(['2026-10' => $this->month(2500000, 'calculated'), '2026-11' => $this->month(2640000)], '2026-11');

        $this->assertTrue($rows[1]['previous']['provisional']);
    }

    public function test_nice_step_and_compact_labels(): void
    {
        $this->assertSame(1, PayrollTrend::niceStep(0));
        $this->assertSame(1000000, PayrollTrend::niceStep(2640000));
        $this->assertSame(250, PayrollTrend::niceStep(1000));
        $this->assertSame(5, PayrollTrend::niceStep(20));

        $this->assertSame('0', PayrollTrend::compact(0));
        $this->assertSame('800 k', PayrollTrend::compact(800000));
        $this->assertSame('1 M', PayrollTrend::compact(1000000));
        $this->assertSame('2,5 M', PayrollTrend::compact(2500000));
        $this->assertSame('4 M', PayrollTrend::compact(4000000));
    }

    public function test_chart_geometry_with_a_gap(): void
    {
        $months = ['2026-08' => $this->month(1000000), '2026-09' => $this->month(2000000), '2026-11' => $this->month(1000000)];
        $c = PayrollTrend::chart(PayrollTrend::series($months, '2026-11', 'net_to_pay'));

        $this->assertSame(2000000, $c['axis_max']);
        $this->assertCount(5, $c['ticks']);
        $this->assertSame(['0', '500 k', '1 M', '1,5 M', '2 M'], array_column($c['ticks'], 'label'));
        $this->assertCount(3, $c['points']);
        $this->assertFalse($c['empty']);
        // Août et septembre se suivent : un trait ; novembre est isolé (octobre manque) : un point sans trait.
        $this->assertCount(1, $c['paths']);
        $this->assertStringStartsWith('M', $c['paths'][0]);
        $this->assertCount(12, $c['xlabels']);
        // L'année s'écrit sous le premier mois et sous chaque janvier.
        $this->assertSame('2025', $c['xlabels'][0]['year']);
        $this->assertSame('2026', $c['xlabels'][1]['year']);
        $this->assertNull($c['xlabels'][2]['year']);
        // Zone de tracé : de y = 16 à y = 226 (hauteur 210). 1 M = moitié de l'axe : y = 121 ; 2 M = sommet : y = 16.
        $this->assertSame('121.0', $c['points'][0]['y']);
        $this->assertSame('16.0', $c['points'][1]['y']);
        $this->assertSame('226.0', $c['ticks'][0]['y']);
    }

    public function test_chart_is_empty_below_two_points(): void
    {
        $c = PayrollTrend::chart(PayrollTrend::series(['2026-11' => $this->month(1000000)], '2026-11', 'net_to_pay'));

        $this->assertTrue($c['empty']);
        $this->assertSame([], $c['paths']);
    }
}
