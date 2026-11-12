<?php

namespace App\Services\Payroll;

/**
 * Vérification du net d'un employé avant clôture : fonction pure.
 *
 * Deux écarts distincts, pour que l'alerte ne se déclenche que sur un vrai problème :
 *  - écart de STRUCTURE = net du contrat (mois complet, sans variables) − net connu : une augmentation non enregistrée,
 *    une règle modifiée ou un net périmé. C'est lui qui déclenche « net différent » ;
 *  - effet des VARIABLES = net à payer − net du contrat : absences, primes, prêts, prorata. Il est expliqué, sans alerte.
 */
final class NetCheck
{
    public const OK = 'ok';
    public const VARIABLES = 'variables';
    public const TO_CHECK = 'to_check';
    public const NO_REFERENCE = 'no_reference';

    /** @return array{status: string, structure_gap: ?int, variables_effect: int} */
    public static function classify(?int $netKnown, int $contractNet, int $netToPay, int $tolerance): array
    {
        $effect = $netToPay - $contractNet;

        if ($netKnown === null) {
            return ['status' => self::NO_REFERENCE, 'structure_gap' => null, 'variables_effect' => $effect];
        }

        $gap = $contractNet - $netKnown;

        if (abs($gap) > max(0, $tolerance)) {
            $status = self::TO_CHECK;
        } elseif ($effect !== 0) {
            $status = self::VARIABLES;
        } else {
            $status = self::OK;
        }

        return ['status' => $status, 'structure_gap' => $gap, 'variables_effect' => $effect];
    }
}
