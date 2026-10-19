<?php

namespace App\Support;

/**
 * Classement d'un profil : niveau d'études et ancienneté PROFESSIONNELLE (années d'expérience).
 * L'âge n'est volontairement ni collecté ni utilisé pour classer ou filtrer les candidats.
 */
final class CandidateProfile
{
    public static function seniority(float|int|string|null $years): ?string
    {
        if ($years === null || $years === '') {
            return null;
        }

        $years = (float) $years;

        foreach (config('recruitment.seniority') as $key => $range) {
            if ($years >= $range['min'] && ($range['max'] === null || $years < $range['max'])) {
                return $key;
            }
        }

        return null;
    }

    public static function seniorityLabel(float|int|string|null $years): string
    {
        $key = self::seniority($years);

        return $key ? config("recruitment.seniority.{$key}.label") : '—';
    }

    /** « moins de 2 ans », « 2 à 5 ans », « 10 ans et plus ». */
    public static function seniorityRange(string $key): string
    {
        $r = config("recruitment.seniority.{$key}");

        if (! $r) {
            return '';
        }
        if ($r['max'] === null) {
            return "{$r['min']} ans et plus";
        }
        if ($r['min'] == 0) {
            return "moins de {$r['max']} ans";
        }

        return $r['min'].' à '.($r['max'] - 1).' ans';
    }

    public static function educationRank(?string $key): int
    {
        return (int) config("recruitment.education_levels.{$key}.rank", 0);
    }

    public static function educationLabel(?string $key): string
    {
        return config("recruitment.education_levels.{$key}.label", '—');
    }

    /** Clés des niveaux d'études égaux ou supérieurs à $key. */
    public static function levelsAtLeast(?string $key): array
    {
        $min = self::educationRank($key);

        return collect(config('recruitment.education_levels'))
            ->filter(fn ($l) => $l['rank'] >= $min)->keys()->all();
    }
}
