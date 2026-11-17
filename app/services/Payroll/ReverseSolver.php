<?php

namespace App\Services\Payroll;

use InvalidArgumentException;

/**
 * Simulateur « à l'envers » : on connaît le brut ou le net voulu, on cherche le SALAIRE DE BASE qui y mène.
 * Même méthode que le net garanti (NetTargetSolver) : l'ITS est progressif, donc on ne peut pas inverser la formule ;
 * on cherche par dichotomie le plus petit salaire de base entier dont le brut (ou le net) atteint la cible.
 * Fonction pure : le calcul d'une paie est fourni par l'appelant.
 */
final class ReverseSolver
{
    public const GROSS = 'gross';

    public const NET = 'net';

    /**
     * @param string $kind GROSS (brut total, rubriques du modèle comprises) ou NET (net du contrat)
     * @param callable(int): array{gross: int, net: int} $figures brut et net obtenus pour un salaire de base donné (croissants avec la base)
     *
     * @return array{kind: string, target: int, base: int, reached: bool, gross: int, net: int, gap: int}
     *               gap = montant obtenu moins cible (positif : on dépasse la cible, par arrondi ou par tranche) ;
     *               reached = false si même la plus grande base essayée n'atteint pas la cible
     */
    public static function solve(string $kind, int $target, callable $figures): array
    {
        if (! in_array($kind, [self::GROSS, self::NET], true)) {
            throw new InvalidArgumentException("Type de cible inconnu : {$kind}.");
        }

        if ($target <= 0) {
            $f = $figures(0);

            return ['kind' => $kind, 'target' => $target, 'base' => 0, 'reached' => false, 'gross' => (int) $f['gross'], 'net' => (int) $f['net'], 'gap' => (int) $f[$kind] - $target];
        }

        $solved = NetTargetSolver::solve(fn (int $base): int => (int) $figures($base)[$kind], $target);
        $f = $figures($solved['supplement']);

        return [
            'kind' => $kind,
            'target' => $target,
            'base' => $solved['supplement'],
            'reached' => $solved['reached'],
            'gross' => (int) $f['gross'],
            'net' => (int) $f['net'],
            'gap' => (int) $f[$kind] - $target,
        ];
    }
}
