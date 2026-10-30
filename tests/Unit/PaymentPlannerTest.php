<?php

namespace Tests\Unit;

use App\Services\PaymentPlanner;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymentPlannerTest extends TestCase
{
    private function dates(): array
    {
        return [CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14')];
    }

    public function test_before_pays_everything_the_day_before_departure(): void
    {
        [$s, $e] = $this->dates();
        $plan = PaymentPlanner::defaultPlan('before', 100000, $s, $e);

        $this->assertCount(1, $plan);
        $this->assertSame(100000, $plan[0]['amount']);
        $this->assertSame('2026-11-09', $plan[0]['date']);
    }

    public function test_after_pays_everything_on_return_date(): void
    {
        [$s, $e] = $this->dates();
        $plan = PaymentPlanner::defaultPlan('after', 100000, $s, $e);

        $this->assertSame('2026-11-14', $plan[0]['date']);
    }

    public function test_tranches_split_in_two_and_always_sum_to_total(): void
    {
        [$s, $e] = $this->dates();
        $plan = PaymentPlanner::defaultPlan('tranches', 100001, $s, $e);

        $this->assertCount(2, $plan);
        $this->assertSame(50000, $plan[0]['amount']);
        $this->assertSame(50001, $plan[1]['amount']);
        $this->assertSame(100001, array_sum(array_column($plan, 'amount')));
    }

    public function test_tranches_with_tiny_amount_fall_back_to_a_single_payment(): void
    {
        [$s, $e] = $this->dates();

        $this->assertCount(1, PaymentPlanner::defaultPlan('tranches', 1, $s, $e));
    }

    public function test_custom_and_zero_total_give_no_plan(): void
    {
        [$s, $e] = $this->dates();

        $this->assertSame([], PaymentPlanner::defaultPlan('custom', 100000, $s, $e));
        $this->assertSame([], PaymentPlanner::defaultPlan('before', 0, $s, $e));
    }

    public function test_default_plan_does_not_mutate_the_given_dates(): void
    {
        $s = \Carbon\Carbon::parse('2026-11-10');
        PaymentPlanner::defaultPlan('before', 1000, $s, \Carbon\Carbon::parse('2026-11-14'));

        $this->assertSame('2026-11-10', $s->toDateString());
    }

    public function test_normalize_accepts_a_schedule_matching_the_total(): void
    {
        $rows = PaymentPlanner::normalize([
            ['label' => 'Avance', 'amount' => '60000', 'date' => '2026-11-09'],
            ['label' => '', 'amount' => 40000, 'date' => ''],
        ], 100000);

        $this->assertSame('Tranche', $rows[1]['label']);
        $this->assertNull($rows[1]['date']);
        $this->assertSame(60000, $rows[0]['amount']);
    }

    public function test_normalize_rejects_a_wrong_sum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PaymentPlanner::normalize([['amount' => 50000]], 100000);
    }

    public function test_normalize_rejects_empty_and_zero_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentPlanner::normalize([], 1000);
    }

    public function test_normalize_rejects_zero_amount_row(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentPlanner::normalize([['amount' => 0], ['amount' => 1000]], 1000);
    }

    public function test_summarize_states(): void
    {
        $this->assertSame('unplanned', PaymentPlanner::summarize(100000, 0, 0)['state']);
        $this->assertSame('unpaid', PaymentPlanner::summarize(100000, 0, 2)['state']);
        $this->assertSame('partial', PaymentPlanner::summarize(100000, 40000, 1)['state']);
        $this->assertSame('settled', PaymentPlanner::summarize(100000, 100000, 0)['state']);
        $this->assertSame(60000, PaymentPlanner::summarize(100000, 40000, 1)['remaining']);
    }
}
