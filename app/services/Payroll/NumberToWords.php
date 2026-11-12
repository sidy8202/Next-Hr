<?php

namespace App\Services\Payroll;

/** Montant en toutes lettres, en français (« deux cent mille quarante-neuf ») : fonction pure. */
final class NumberToWords
{
    private const UNITS = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    public static function fr(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        if ($n < 0) {
            return 'moins ' . self::fr(-$n);
        }

        $words = [];

        foreach ([[1_000_000_000, 'milliard', 'milliards'], [1_000_000, 'million', 'millions']] as [$size, $one, $many]) {
            $q = intdiv($n, $size);
            $n %= $size;

            if ($q > 0) {
                // « deux cents millions », « quatre-vingts millions » : ces mots prennent leur s devant un nom
                $words[] = self::below1000($q, true) . ' ' . ($q === 1 ? $one : $many);
            }
        }

        $thousands = intdiv($n, 1000);
        $n %= 1000;

        if ($thousands > 0) {
            // « mille » est invariable et « cent » et « vingt » ne prennent pas de s devant lui : « deux cent mille »
            $words[] = $thousands === 1 ? 'mille' : self::below1000($thousands, false) . ' mille';
        }

        if ($n > 0) {
            $words[] = self::below1000($n, true);
        }

        return implode(' ', $words);
    }

    /** Première lettre en capitale : « Deux cent mille quarante-neuf ». */
    public static function frCapitalized(int $n): string
    {
        $text = self::fr($n);

        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    private static function below1000(int $n, bool $final): string
    {
        $hundreds = intdiv($n, 100);
        $rest = $n % 100;
        $out = [];

        if ($hundreds > 0) {
            $h = $hundreds === 1 ? 'cent' : self::UNITS[$hundreds] . ' cent';

            if ($hundreds > 1 && $rest === 0 && $final) {
                $h .= 's';
            }

            $out[] = $h;
        }

        if ($rest > 0) {
            $out[] = self::below100($rest, $final);
        }

        return implode(' ', $out);
    }

    private static function below100(int $n, bool $final): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }

        $t = intdiv($n, 10);
        $u = $n % 10;

        if ($t === 7 || $t === 9) {
            if ($t === 7 && $u === 1) {
                return 'soixante et onze';
            }

            return ($t === 7 ? 'soixante' : 'quatre-vingt') . '-' . self::UNITS[10 + $u];
        }

        if ($t === 8) {
            return $u === 0 ? ($final ? 'quatre-vingts' : 'quatre-vingt') : 'quatre-vingt-' . self::UNITS[$u];
        }

        $tens = self::TENS[$t];

        return $u === 0 ? $tens : ($u === 1 ? $tens . ' et un' : $tens . '-' . self::UNITS[$u]);
    }
}
