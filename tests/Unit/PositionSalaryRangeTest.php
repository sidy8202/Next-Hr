<?php

namespace Tests\Unit;

use App\Services\Payroll\PositionSalaryRange;
use PHPUnit\Framework\TestCase;

class PositionSalaryRangeTest extends TestCase
{
    public function test_range_is_smallest_and_largest_step(): void
    {
        $this->assertSame(['min' => 100000, 'max' => 300000], PositionSalaryRange::range([300000, 100000, 200000]));
    }

    /** Les montants non numériques, nuls ou négatifs ne comptent pas ; les nombres écrits en texte sont lus. */
    public function test_range_ignores_invalid_amounts(): void
    {
        $this->assertSame(['min' => 250000, 'max' => 300000], PositionSalaryRange::range([300000, '250000', null, 0, -5, 'abc']));
    }

    public function test_range_without_usable_amount_is_null(): void
    {
        $this->assertNull(PositionSalaryRange::range([]));
        $this->assertNull(PositionSalaryRange::range([0, null, -10]));
    }

    public function test_single_step_gives_equal_bounds(): void
    {
        $this->assertSame(['min' => 180000, 'max' => 180000], PositionSalaryRange::range([180000]));
    }
}
