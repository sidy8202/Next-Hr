<?php

namespace Tests\Unit;

use App\Services\Payroll\ReverseSolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReverseSolverTest extends TestCase
{
    /** Modèle simple : 50 000 de rubriques fixes, le net vaut 80 % du brut. */
    private function linear(): callable
    {
        return fn (int $base): array => ['gross' => $base + 50000, 'net' => intdiv(($base + 50000) * 8, 10)];
    }

    public function test_gross_target_finds_the_base(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::GROSS, 400000, $this->linear());

        $this->assertSame(350000, $r['base']);
        $this->assertTrue($r['reached']);
        $this->assertSame(400000, $r['gross']);
        $this->assertSame(320000, $r['net']);
        $this->assertSame(0, $r['gap']);
        $this->assertSame('gross', $r['kind']);
    }

    public function test_net_target_finds_the_base(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::NET, 320000, $this->linear());

        $this->assertSame(350000, $r['base']);
        $this->assertSame(400000, $r['gross']);
        $this->assertSame(320000, $r['net']);
        $this->assertSame(0, $r['gap']);
    }

    /** Le plus petit salaire de base qui ATTEINT la cible : la base juste en dessous ne l'atteint pas. */
    public function test_result_is_the_smallest_base_reaching_the_target(): void
    {
        $figures = $this->linear();
        $r = ReverseSolver::solve(ReverseSolver::NET, 300001, $figures);

        $this->assertGreaterThanOrEqual(300001, $figures($r['base'])['net']);
        $this->assertLessThan(300001, $figures($r['base'] - 1)['net']);
    }

    /** Montants par paliers de 1 000 : la cible 250 500 est dépassée de 500, l'écart est annoncé. */
    public function test_overshoot_is_reported_as_a_positive_gap(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::NET, 250500, fn (int $base): array => ['gross' => $base, 'net' => intdiv($base, 1000) * 1000]);

        $this->assertTrue($r['reached']);
        $this->assertSame(251000, $r['net']);
        $this->assertSame(500, $r['gap']);
    }

    /** Rubriques fixes déjà au-dessus de la cible : base nulle, cible dépassée. */
    public function test_fixed_items_above_the_target_give_a_zero_base(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::GROSS, 80000, fn (int $base): array => ['gross' => $base + 100000, 'net' => $base + 80000]);

        $this->assertSame(0, $r['base']);
        $this->assertTrue($r['reached']);
        $this->assertSame(20000, $r['gap']);
    }

    public function test_unreachable_target_is_reported(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::GROSS, 500000, fn (int $base): array => ['gross' => 100000, 'net' => 80000]);

        $this->assertFalse($r['reached']);
        $this->assertSame(-400000, $r['gap']);
    }

    public function test_non_positive_target_is_not_reached(): void
    {
        $r = ReverseSolver::solve(ReverseSolver::NET, 0, $this->linear());

        $this->assertFalse($r['reached']);
        $this->assertSame(0, $r['base']);
    }

    public function test_unknown_kind_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReverseSolver::solve('autre', 1000, $this->linear());
    }
}
