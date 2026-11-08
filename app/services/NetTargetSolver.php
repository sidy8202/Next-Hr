<?php

namespace App\Services\Payroll;

/**
 * Net garanti : on cherche le sursalaire qui donne le net convenu. L'ITS est progressif, donc on ne peut pas inverser la formule :
 * on cherche par dichotomie le PLUS PETIT sursalaire entier dont le net atteint la cible. Fonction pure.
 */
final class NetTargetSolver
{
    /**
     * @param callable(int): int $netFor net obtenu pour un sursalaire donné (doit être croissant)
     * @return array{supplement: int, net: int, reached: bool}
     */
    public static function solve(callable $netFor, int $target, int $low = 0, ?int $high = null): array
    {
        $atLow = $netFor($low);

        if ($atLow >= $target) {
            return ['supplement' => $low, 'net' => $atLow, 'reached' => true];
        }

        $high ??= max(1_000_000, $target * 3);

        for ($i = 0; $i < 12 && $netFor($high) < $target; $i++) {
            $low = $high;
            $high *= 2;
        }

        if ($netFor($high) < $target) {
            return ['supplement' => $high, 'net' => $netFor($high), 'reached' => false];
        }

        while ($high - $low > 1) {
            $mid = intdiv($low + $high, 2);

            if ($netFor($mid) >= $target) {
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        return ['supplement' => $high, 'net' => $netFor($high), 'reached' => true];
    }
}
