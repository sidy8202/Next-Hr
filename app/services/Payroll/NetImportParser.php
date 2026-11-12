<?php

namespace App\Services\Payroll;

/** Lit une liste « matricule ; net » collée par l'utilisateur : fonction pure. */
final class NetImportParser
{
    /**
     * Séparateurs acceptés : point-virgule, tabulation, virgule ou deux-points. Les espaces dans le montant sont ignorés (« 200 049 »).
     *
     * @return array{pairs: array<string, int>, invalid: array<int, string>}
     */
    public static function parse(string $text): array
    {
        $pairs = [];
        $invalid = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[;\t,:]/u', $line, 2);
            $matricule = trim($parts[0] ?? '');
            $digits = preg_replace('/\D/', '', $parts[1] ?? '');

            if ($matricule === '' || count($parts) < 2 || $digits === '' || (int) $digits <= 0) {
                $invalid[] = $line;

                continue;
            }

            $pairs[$matricule] = (int) $digits;
        }

        return ['pairs' => $pairs, 'invalid' => $invalid];
    }
}
