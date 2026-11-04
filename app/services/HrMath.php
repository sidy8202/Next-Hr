<?php

namespace App\Services;

/** Calculs RH purs (sans base de données) : testables seuls. */
final class HrMath
{
    public const SENIORITY = ['< 1 an', '1 à 3 ans', '3 à 5 ans', '5 à 10 ans', '10 ans et +'];
    public const AGES = ['< 25 ans', '25 à 34 ans', '35 à 44 ans', '45 à 54 ans', '55 ans et +', 'Non renseigné'];

    /** Taux de rotation en % = départs / effectif moyen (début et fin de période). Null si l'effectif moyen est nul. */
    public static function turnover(int $exits, int $startHead, int $endHead): ?float
    {
        $avg = ($startHead + $endHead) / 2;

        return $avg > 0 ? round($exits / $avg * 100, 1) : null;
    }

    /** Années écoulées entre deux dates Y-m-d (jamais négatif). */
    public static function years(string $from, string $to): float
    {
        $a = strtotime($from);
        $b = strtotime($to);

        return ($a === false || $b === false || $b < $a) ? 0.0 : ($b - $a) / (365.25 * 86400);
    }

    public static function ageAt(?string $birth, string $at): ?int
    {
        if (! $birth || strtotime($birth) === false) {
            return null;
        }

        $age = (int) floor(self::years(substr($birth, 0, 10), $at));

        return $age >= 14 && $age <= 90 ? $age : null;   // valeurs aberrantes ignorées
    }

    /** F, M ou N (non renseigné) à partir des écritures courantes. */
    public static function genderKey(?string $g): string
    {
        $g = mb_strtolower(trim((string) $g));

        return match (true) {
            $g === '' => 'N',
            in_array($g, ['f', 'femme', 'female', 'féminin', 'feminin', 'woman'], true) => 'F',
            in_array($g, ['m', 'h', 'homme', 'male', 'masculin', 'man'], true) => 'M',
            default => 'N',
        };
    }

    public static function seniorityIndex(float $years): int
    {
        return match (true) { $years < 1 => 0, $years < 3 => 1, $years < 5 => 2, $years < 10 => 3, default => 4 };
    }

    public static function ageIndex(?int $age): int
    {
        return match (true) { $age === null => 5, $age < 25 => 0, $age < 35 => 1, $age < 45 => 2, $age < 55 => 3, default => 4 };
    }

    /** Libellé français d'un type de sortie ; une valeur inconnue est affichée lisiblement, jamais perdue. */
    public static function terminationLabel(?string $type): string
    {
        $t = mb_strtolower(trim((string) $type));

        $map = [
            'resignation' => 'Démission', 'demission' => 'Démission', 'démission' => 'Démission',
            'dismissal' => 'Licenciement', 'licenciement' => 'Licenciement', 'fired' => 'Licenciement',
            'end_of_contract' => 'Fin de contrat', 'contract_end' => 'Fin de contrat', 'fin_de_contrat' => 'Fin de contrat', 'fin de contrat' => 'Fin de contrat', 'expiry' => 'Fin de contrat',
            'retirement' => 'Retraite', 'retraite' => 'Retraite',
            'death' => 'Décès', 'deces' => 'Décès', 'décès' => 'Décès',
            'mutual' => 'Rupture conventionnelle', 'mutual_agreement' => 'Rupture conventionnelle', 'rupture_conventionnelle' => 'Rupture conventionnelle',
            'probation' => 'Fin de période d’essai', 'trial_end' => 'Fin de période d’essai',
            'abandonment' => 'Abandon de poste', 'abandon' => 'Abandon de poste',
            'other' => 'Autre', 'autre' => 'Autre', '' => 'Non précisé',
        ];

        return $map[$t] ?? ucfirst(str_replace('_', ' ', $t));
    }
}
