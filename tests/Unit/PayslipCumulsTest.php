<?php

namespace Tests\Unit;

use App\Services\Payroll\PayslipCumuls;
use PHPUnit\Framework\TestCase;

class PayslipCumulsTest extends TestCase
{
    private function row(string $period, int $gross = 286919, int $net = 200096): array
    {
        return ['period' => $period, 'gross' => $gross, 'its_base' => 276590, 'employee_contributions' => 66823, 'its' => 47714, 'net_to_pay' => $net];
    }

    public function test_cumuls_add_up_the_months_of_the_year_up_to_the_period(): void
    {
        $rows = [$this->row('2026-01'), $this->row('2026-02'), $this->row('2026-03')];

        $c = PayslipCumuls::upTo($rows, '2026-03');

        $this->assertSame(3, $c['months']);
        $this->assertSame(860757, $c['gross']);
        $this->assertSame(829770, $c['taxable_base']);
        $this->assertSame(200469, $c['contributions']);
        $this->assertSame(143142, $c['its']);
        $this->assertSame(600288, $c['net']);
    }

    public function test_later_months_and_other_years_are_left_out(): void
    {
        $rows = [$this->row('2025-12'), $this->row('2026-01'), $this->row('2026-02'), $this->row('2026-03')];

        $c = PayslipCumuls::upTo($rows, '2026-02');

        $this->assertSame(2, $c['months']);
        $this->assertSame(573838, $c['gross']);
    }

    public function test_no_rows(): void
    {
        $this->assertSame(['months' => 0, 'gross' => 0, 'taxable_base' => 0, 'contributions' => 0, 'its' => 0, 'net' => 0], PayslipCumuls::upTo([], '2026-11'));
    }
}
