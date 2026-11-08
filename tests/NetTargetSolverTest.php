<?php

namespace Tests\Unit;

use App\Services\Payroll\NetTargetSolver;
use PHPUnit\Framework\TestCase;

class NetTargetSolverTest extends TestCase
{
    public function test_finds_the_smallest_supplement_reaching_the_target(): void
    {
        $net = fn (int $s): int => intdiv($s * 7, 10) + 1000;          // net = 70 % du sursalaire + 1 000

        $result = NetTargetSolver::solve($net, 8000);

        $this->assertTrue($result['reached']);
        $this->assertSame(10000, $result['supplement']);
        $this->assertSame(8000, $result['net']);
        $this->assertLessThan(8000, $net(9999));                        // un franc de moins ne suffit pas
    }

    public function test_a_salary_already_above_the_target_needs_no_supplement(): void
    {
        $result = NetTargetSolver::solve(fn (int $s): int => 9000 + $s, 8000);

        $this->assertSame(0, $result['supplement']);
        $this->assertSame(9000, $result['net']);
        $this->assertTrue($result['reached']);
    }

    public function test_an_unreachable_target_is_reported(): void
    {
        $result = NetTargetSolver::solve(fn (int $s): int => min($s, 5000), 8000);

        $this->assertFalse($result['reached']);
    }

    public function test_it_works_with_a_progressive_function(): void
    {
        $net = fn (int $s): int => $s <= 100000 ? $s : 100000 + intdiv($s - 100000, 2);

        $result = NetTargetSolver::solve($net, 150000);

        $this->assertSame(200000, $result['supplement']);
        $this->assertSame(150000, $result['net']);
    }
}
