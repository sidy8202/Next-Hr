<?php

namespace App\Services\Payroll;

/**
 * Choix PUR de la version de chaque paquet proposée aux entreprises.
 * Pour un même code : la dernière version VALIDÉE s'il y en a une ; sinon la dernière version « à valider » (avec bandeau d'avertissement).
 * Les coquilles vides (rien à appliquer) ne sont jamais proposées.
 */
class PackageCatalog
{
    /**
     * @param array<int, array{id: int, code: string, version: int, status: string, empty: bool}> $rows
     *
     * @return array<int, array{id: int, code: string, version: int, status: string, empty: bool}> triés par code
     */
    public static function offered(array $rows): array
    {
        $best = [];

        foreach ($rows as $row) {
            if ($row['empty']) {
                continue;
            }

            $code = $row['code'];

            if (! isset($best[$code]) || self::better($row, $best[$code])) {
                $best[$code] = $row;
            }
        }

        ksort($best);

        return array_values($best);
    }

    /** @param array{version: int, status: string} $a */
    protected static function better(array $a, array $b): bool
    {
        $aValidated = $a['status'] === 'validated';
        $bValidated = $b['status'] === 'validated';

        if ($aValidated !== $bValidated) {
            return $aValidated;
        }

        return $a['version'] > $b['version'];
    }
}
