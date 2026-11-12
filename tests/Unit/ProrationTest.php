<?php

namespace Tests\Unit;

use App\Services\Payroll\Proration;
use PHPUnit\Framework\TestCase;

class ProrationTest extends TestCase
{
    public function test_a_full_month_gives_one(): void
    {
        $this->assertSame(1.0, Proration::presenceFactor('2025-01-01', null, '2026-11-01', '2026-11-30'));
        $this->assertSame(1.0, Proration::presenceFactor(null, null, '2026-12-01', '2026-12-31'));
        $this->assertSame(1.0, Proration::presenceFactor('2020-05-10', null, '2026-02-01', '2026-02-28'));     // février complet : toujours 1
    }

    public function test_arriving_on_the_16th_leaves_15_days_out_of_30(): void
    {
        $this->assertSame(0.5, Proration::presenceFactor('2026-11-16', null, '2026-11-01', '2026-11-30'));
    }

    public function test_leaving_on_the_10th(): void
    {
        $this->assertEqualsWithDelta(10 / 30, Proration::presenceFactor('2020-01-01', '2026-11-10', '2026-11-01', '2026-11-30'), 0.0001);
    }

    public function test_arriving_and_leaving_in_the_same_month(): void
    {
        // présent du 10 au 19 novembre : 10 jours, 20 absents → 10/30
        $this->assertEqualsWithDelta(10 / 30, Proration::presenceFactor('2026-11-10', '2026-11-19', '2026-11-01', '2026-11-30'), 0.0001);
    }

    public function test_a_31_day_month_uses_the_30_day_basis(): void
    {
        // arrivé le 20 décembre : présent 12 jours sur 31, donc 19 absents : (30 − 19) / 30
        $this->assertEqualsWithDelta(11 / 30, Proration::presenceFactor('2026-12-20', null, '2026-12-01', '2026-12-31'), 0.0001);
    }

    public function test_not_present_at_all_gives_zero(): void
    {
        $this->assertSame(0.0, Proration::presenceFactor('2026-12-05', null, '2026-11-01', '2026-11-30'));
        $this->assertSame(0.0, Proration::presenceFactor('2020-01-01', '2026-10-31', '2026-11-01', '2026-11-30'));
    }
}
