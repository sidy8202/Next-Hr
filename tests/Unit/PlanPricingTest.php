<?php

namespace Tests\Unit;

use App\Services\PlanPricing;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlanPricingTest extends TestCase
{
    public function test_net_price_applies_the_discount(): void
    {
        $this->assertSame(85000, PlanPricing::net(100000, 15));
        $this->assertSame(100000, PlanPricing::net(100000, 0));
        $this->assertSame(0, PlanPricing::net(100000, 100));
    }

    public function test_net_price_rounds_to_the_nearest_unit(): void
    {
        $this->assertSame(95001, PlanPricing::net(100001, 5));
    }

    public function test_invalid_discount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlanPricing::net(100000, 120);
    }

    public function test_monthly_equivalent(): void
    {
        $this->assertSame(30000, PlanPricing::monthly(360000, 12));
        $this->assertSame(0, PlanPricing::monthly(1000, 0));
    }
}
