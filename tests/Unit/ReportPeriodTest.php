<?php

namespace Tests\Unit;

use App\Services\ReportPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ReportPeriodTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-15');
    }

    public function test_twelve_months_starts_eleven_months_back_at_month_start(): void
    {
        [$f, $t] = ReportPeriod::resolve('12m', null, null, $this->now());

        $this->assertSame('2025-12-01', $f->toDateString());
        $this->assertSame('2026-11-15', $t->toDateString());
    }

    public function test_quarter_and_year_presets(): void
    {
        $this->assertSame('2026-10-01', ReportPeriod::resolve('quarter', null, null, $this->now())[0]->toDateString());
        $this->assertSame('2026-01-01', ReportPeriod::resolve('year', null, null, $this->now())[0]->toDateString());
    }

    public function test_last_month_covers_the_whole_previous_month(): void
    {
        [$f, $t] = ReportPeriod::resolve('last_month', null, null, $this->now());

        $this->assertSame('2026-10-01', $f->toDateString());
        $this->assertSame('2026-10-31', $t->toDateString());
    }

    public function test_custom_period_is_used_when_valid(): void
    {
        [$f, $t] = ReportPeriod::resolve('custom', '2026-03-01', '2026-03-31', $this->now());

        $this->assertSame('2026-03-01', $f->toDateString());
        $this->assertSame('2026-03-31', $t->toDateString());
    }

    public function test_custom_period_falls_back_to_twelve_months_when_invalid(): void
    {
        [$f] = ReportPeriod::resolve('custom', '2026-05-10', '2026-05-01', $this->now());
        $this->assertSame('2025-12-01', $f->toDateString());

        [$g] = ReportPeriod::resolve('custom', 'n’importe quoi', null, $this->now());
        $this->assertSame('2025-12-01', $g->toDateString());
    }

    public function test_previous_period_has_the_same_length_and_ends_the_day_before(): void
    {
        $from = CarbonImmutable::parse('2026-10-01');
        $to = CarbonImmutable::parse('2026-10-31');

        [$pf, $pt] = ReportPeriod::previous($from, $to, 'previous');

        $this->assertSame('2026-09-30', $pt->toDateString());
        $this->assertSame('2026-08-31', $pf->toDateString());
    }

    public function test_year_comparison_and_none(): void
    {
        $from = CarbonImmutable::parse('2026-10-01');
        $to = CarbonImmutable::parse('2026-10-31');

        $this->assertSame('2025-10-01', ReportPeriod::previous($from, $to, 'year')[0]->toDateString());
        $this->assertNull(ReportPeriod::previous($from, $to, 'none'));
    }

    public function test_months_lists_every_month_touched(): void
    {
        $months = ReportPeriod::months(CarbonImmutable::parse('2026-09-20'), CarbonImmutable::parse('2026-11-02'));

        $this->assertCount(3, $months);
        $this->assertSame('2026-09-01', $months[0]->toDateString());
        $this->assertSame('2026-11-01', $months[2]->toDateString());
    }

    public function test_delta_and_size_brackets(): void
    {
        $this->assertSame(50, ReportPeriod::delta(150, 100));
        $this->assertSame(-25, ReportPeriod::delta(75, 100));
        $this->assertNull(ReportPeriod::delta(10, 0));

        $this->assertSame('0-25', ReportPeriod::sizeBracket(25));
        $this->assertSame('26-55', ReportPeriod::sizeBracket(26));
        $this->assertSame('26-55', ReportPeriod::sizeBracket(55));
        $this->assertSame('251+', ReportPeriod::sizeBracket(300));
    }
}
