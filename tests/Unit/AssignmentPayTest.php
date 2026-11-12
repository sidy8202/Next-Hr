<?php

namespace Tests\Unit;

use App\Services\Payroll\AssignmentPay;
use PHPUnit\Framework\TestCase;

class AssignmentPayTest extends TestCase
{
    public function test_fraction_of_the_month(): void
    {
        $this->assertSame(0.5, AssignmentPay::fraction(15, 30));
        $this->assertSame(1.0, AssignmentPay::fraction(31, 30));   // jamais plus que le mois entier
        $this->assertSame(0.0, AssignmentPay::fraction(0, 30));
        $this->assertSame(0.0, AssignmentPay::fraction(10, 0));
    }

    /** Employé B (266 919 de référence) remplace un poste à 400 000 du 10 au 24 novembre 2026 : 15 jours sur 30. */
    public function test_position_salary_difference_is_prorated(): void
    {
        $this->assertSame(66541, AssignmentPay::positionSalaryAmount(400000, 266919, 15, 30, 'difference'));  // 133 081 × 15/30 = 66 540,5
        $this->assertSame(66540, AssignmentPay::positionSalaryAmount(400000, 266919, 15, 30, 'difference', 'floor'));
    }

    public function test_position_salary_full_basis_ignores_the_employee_salary(): void
    {
        $this->assertSame(200000, AssignmentPay::positionSalaryAmount(400000, 266919, 15, 30, 'full'));
    }

    public function test_a_position_paid_less_than_the_employee_gives_nothing_in_difference_mode(): void
    {
        $this->assertSame(0, AssignmentPay::positionSalaryAmount(200000, 266919, 15, 30, 'difference'));
        $this->assertSame(100000, AssignmentPay::positionSalaryAmount(200000, 266919, 15, 30, 'full'));
    }

    public function test_a_full_month_gives_the_whole_difference(): void
    {
        $this->assertSame(133081, AssignmentPay::positionSalaryAmount(400000, 266919, 30, 30, 'difference'));
        $this->assertSame(133081, AssignmentPay::positionSalaryAmount(400000, 266919, 31, 31, 'difference'));
    }

    public function test_allowance_amount_rounding(): void
    {
        $this->assertSame(25000, AssignmentPay::allowanceAmount(25000.0));
        $this->assertSame(60001, AssignmentPay::allowanceAmount(60000.5));
        $this->assertSame(60000, AssignmentPay::allowanceAmount(60000.5, 'floor'));
    }
}
