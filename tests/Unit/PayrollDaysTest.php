<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollDays;
use PHPUnit\Framework\TestCase;

class PayrollDaysTest extends TestCase
{
    public function test_daily_rate_and_deduction_use_the_configured_days_basis(): void
    {
        $this->assertSame(10000.0, PayrollDays::dailyRate(300000, 30));
        $this->assertSame(25000, PayrollDays::deduction(300000, 30, 2.5));
        $this->assertSame(0, PayrollDays::deduction(300000, 30, 0));
        $this->assertSame(0.0, PayrollDays::dailyRate(300000, 0));
    }

    /** Employé B (bulletin 2) : base 55 000 + sursalaire 211 919 = 266 919 ; 3 jours d'absence non rémunérée. */
    public function test_employee_b_three_unpaid_days(): void
    {
        $this->assertSame(26692, PayrollDays::deduction(266919, 30, 3));          // 26 691,9 arrondi
        $this->assertSame(26691, PayrollDays::deduction(266919, 30, 3, 'floor'));
        $this->assertSame(26692, PayrollDays::deduction(266919, 30, 3, 'ceil'));
    }

    public function test_overtime_amount_applies_the_company_majoration(): void
    {
        // 300 000 / 150 h = 2 000 F l'heure ; 10 h à +25 % = 25 000
        $this->assertSame(25000, PayrollDays::overtimeAmount(300000, 150, 10, 25));
        $this->assertSame(20000, PayrollDays::overtimeAmount(300000, 150, 10, 0));
        $this->assertSame(0, PayrollDays::overtimeAmount(300000, 0, 10, 25));
    }

    public function test_worked_days_are_paid_at_the_daily_rate_with_optional_majoration(): void
    {
        $this->assertSame(20000, PayrollDays::workedDaysAmount(300000, 30, 2, 0));
        $this->assertSame(30000, PayrollDays::workedDaysAmount(300000, 30, 2, 50));
    }

    public function test_month_bounds(): void
    {
        $this->assertSame(['2026-02-01', '2026-02-28'], PayrollDays::monthBounds('2026-02'));
        $this->assertSame(['2028-02-01', '2028-02-29'], PayrollDays::monthBounds('2028-02'));
        $this->assertSame(['2026-12-01', '2026-12-31'], PayrollDays::monthBounds('2026-12'));
    }

    /** Du lundi 9 au dimanche 15 novembre 2026. */
    public function test_working_and_calendar_days_of_a_suspension(): void
    {
        $this->assertSame(7, PayrollDays::countDays('2026-11-09', '2026-11-15', 'calendar'));
        $this->assertSame(6, PayrollDays::countDays('2026-11-09', '2026-11-15', 'working', [0]));      // lundi au samedi
        $this->assertSame(5, PayrollDays::countDays('2026-11-09', '2026-11-15', 'working', [0, 6]));   // lundi au vendredi
        $this->assertSame(5, PayrollDays::countDays('2026-11-09', '2026-11-15', 'working', [0], ['2026-11-12']));
    }

    /** Du jeudi 29 octobre au mercredi 4 novembre 2026 : seule la partie de novembre compte pour la paie de novembre. */
    public function test_a_period_overlapping_two_months_is_clipped_to_the_pay_month(): void
    {
        $this->assertSame(4, PayrollDays::countDays('2026-10-29', '2026-11-04', 'calendar', [0], [], '2026-11-01', '2026-11-30'));
        $this->assertSame(3, PayrollDays::countDays('2026-10-29', '2026-11-04', 'working', [0], [], '2026-11-01', '2026-11-30'));
        $this->assertSame(0, PayrollDays::countDays('2026-10-01', '2026-10-05', 'calendar', [0], [], '2026-11-01', '2026-11-30'));
        $this->assertSame(0, PayrollDays::countDays('2026-11-10', '2026-11-09', 'calendar'));
    }

    public function test_proportional_days_split_an_absence_between_months(): void
    {
        // 6 jours calendaires du 29/10 au 3/11 dont 3 en novembre : la moitié des 6 jours déclarés
        $this->assertSame(3.0, PayrollDays::proportionalDays(6, '2026-10-29', '2026-11-03', '2026-11-01', '2026-11-30'));
        $this->assertSame(3.0, PayrollDays::proportionalDays(6, '2026-10-29', '2026-11-03', '2026-10-01', '2026-10-31'));
        $this->assertSame(2.0, PayrollDays::proportionalDays(2, '2026-11-10', '2026-11-11', '2026-11-01', '2026-11-30'));
        $this->assertSame(0.0, PayrollDays::proportionalDays(2, '2026-10-10', '2026-10-11', '2026-11-01', '2026-11-30'));
        $this->assertSame(0.0, PayrollDays::proportionalDays(0, '2026-11-10', '2026-11-11', '2026-11-01', '2026-11-30'));
    }
}
