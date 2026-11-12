<?php

namespace App\Services\Payroll;

/** Empreinte des données d'un calcul : si elle change entre le calcul et la clôture, la paie doit être recalculée. Fonction pure. */
final class PayrollFingerprint
{
    public static function hash(array $input): string
    {
        return md5((string) json_encode(self::canonical($input), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    /** L'ordre des clés d'un tableau associatif n'a pas d'importance ; celui d'une liste en a. */
    protected static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $value = array_map(fn ($v) => self::canonical($v), $value);

        if (! $isList) {
            ksort($value);
        }

        return $value;
    }
}
