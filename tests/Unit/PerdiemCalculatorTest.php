<?php

namespace Tests\Unit;

use App\Services\PerdiemCalculator;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PerdiemCalculatorTest extends TestCase
{
    public function test_days_are_inclusive(): void
    {
        $days = PerdiemCalculator::days(
            CarbonImmutable::parse('2026-11-02'),
            CarbonImmutable::parse('2026-11-05'),
        );

        $this->assertSame(4, $days);
    }

    public function test_single_day_mission_is_one_day(): void
    {
        $d = CarbonImmutable::parse('2026-11-02');

        $this->assertSame(1, PerdiemCalculator::days($d, $d));
    }

    public function test_return_before_departure_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PerdiemCalculator::days(
            CarbonImmutable::parse('2026-11-05'),
            CarbonImmutable::parse('2026-11-02'),
        );
    }

    public function test_daily_salary_uses_divisor(): void
    {
        $this->assertSame(10000, PerdiemCalculator::dailyFromMonthlySalary(300000, 30));
        $this->assertSame(11538, PerdiemCalculator::dailyFromMonthlySalary(300000, 26));
    }

    public function test_unknown_divisor_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PerdiemCalculator::dailyFromMonthlySalary(300000, 28);
    }

    public function test_total_is_rate_times_days(): void
    {
        $this->assertSame(100000, PerdiemCalculator::total(25000, 4));
    }
}
