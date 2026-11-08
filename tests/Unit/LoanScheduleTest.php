<?php

namespace Tests\Unit;

use App\Services\Payroll\LoanSchedule;
use PHPUnit\Framework\TestCase;

class LoanScheduleTest extends TestCase
{
    public function test_add_months_crosses_year_boundaries(): void
    {
        $this->assertSame('2027-01', LoanSchedule::addMonths('2026-12', 1));
        $this->assertSame('2026-11', LoanSchedule::addMonths('2026-11', 0));
        $this->assertSame('2027-11', LoanSchedule::addMonths('2026-11', 12));
        $this->assertSame('2026-12', LoanSchedule::addMonths('2027-01', -1));
    }

    public function test_the_last_installment_takes_the_remainder(): void
    {
        $rows = LoanSchedule::generate(150000, 40000, '2026-11');

        $this->assertSame([
            ['month' => '2026-11', 'amount' => 40000],
            ['month' => '2026-12', 'amount' => 40000],
            ['month' => '2027-01', 'amount' => 40000],
            ['month' => '2027-02', 'amount' => 30000],
        ], $rows);
        $this->assertSame(150000, array_sum(array_column($rows, 'amount')));
    }

    public function test_an_exact_division_has_no_remainder_installment(): void
    {
        $rows = LoanSchedule::generate(160000, 40000, '2026-11');

        $this->assertCount(4, $rows);
        $this->assertSame(40000, $rows[3]['amount']);
    }

    public function test_an_installment_larger_than_the_loan_makes_a_single_payment(): void
    {
        $this->assertSame([['month' => '2026-11', 'amount' => 30000]], LoanSchedule::generate(30000, 50000, '2026-11'));
        $this->assertSame([], LoanSchedule::generate(0, 40000, '2026-11'));
        $this->assertSame([], LoanSchedule::generate(100000, 0, '2026-11'));
    }

    /** Exemple du cadrage : 40 000 d'échéance sur 100 000 de salaire de référence = 40 %. */
    public function test_effort_percent(): void
    {
        $this->assertSame(40.0, LoanSchedule::effortPercent(40000, 100000));
        $this->assertSame(15.0, LoanSchedule::effortPercent(40000, 266919));   // employé B
        $this->assertNull(LoanSchedule::effortPercent(40000, 0));
    }

    public function test_early_repayment_reduces_the_last_installments_first(): void
    {
        $result = LoanSchedule::applyEarlyRepayment([40000, 40000, 30000], 50000);

        $this->assertSame([40000, 20000, 0], $result['amounts']);
        $this->assertSame(0, $result['surplus']);
    }

    public function test_early_repayment_above_the_balance_reports_the_surplus(): void
    {
        $result = LoanSchedule::applyEarlyRepayment([10000], 15000);

        $this->assertSame([0], $result['amounts']);
        $this->assertSame(5000, $result['surplus']);
        $this->assertSame([40000], LoanSchedule::applyEarlyRepayment([40000], 0)['amounts']);
    }

    public function test_reschedule_spreads_suspended_installments_on_successive_months(): void
    {
        $this->assertSame(['2027-01', '2027-02', '2027-03'], LoanSchedule::reschedule(3, '2027-01'));
        $this->assertSame([], LoanSchedule::reschedule(0, '2027-01'));
    }
}
