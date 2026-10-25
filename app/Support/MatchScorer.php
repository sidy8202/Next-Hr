<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\JobOffer;
use Illuminate\Support\Str;

/**
 * Score de correspondance (0-100) entre un candidat et les critères d'une offre.
 * Transparent et explicable : chaque point du score est détaillé. Il TRIE et SIGNALE, il ne refuse jamais personne tout seul.
 * L'âge, le genre, la nationalité ou la photo n'interviennent jamais.
 */
final class MatchScorer
{
    /** @return array{score: ?int, details: array<int, string>} score = null si l'offre n'a aucun critère */
    public static function score(JobOffer $offer, Candidate $candidate): array
    {
        $w = config('careers.weights');
        $earned = 0.0;
        $max = 0.0;
        $details = [];

        // Domaine (seulement si l'offre ET le candidat en ont un)
        if ($offer->profile_category && $candidate->profile_category) {
            $ok = $offer->profile_category === $candidate->profile_category;
            $max += $w['domain'];
            $earned += $ok ? $w['domain'] : 0;
            $details[] = $ok ? 'Domaine correspondant' : 'Domaine différent';
        }

        // Études minimales (un niveau en dessous = moitié des points)
        if ($offer->min_education_level) {
            $need = CandidateProfile::educationRank($offer->min_education_level);
            $have = CandidateProfile::educationRank($candidate->education_level);
            $max += $w['education'];
            $earned += $have >= $need ? $w['education'] : ($have === $need - 1 ? $w['education'] / 2 : 0);
            $details[] = 'Études : '.CandidateProfile::educationLabel($candidate->education_level);
        }

        // Expérience minimale (proportionnelle)
        if ((float) $offer->min_experience_years > 0) {
            $ratio = min(1, (float) $candidate->experience_years / (float) $offer->min_experience_years);
            $max += $w['experience'];
            $earned += $w['experience'] * $ratio;
            $details[] = 'Expérience : '.self::years($candidate->experience_years).' sur '.self::years($offer->min_experience_years).' demandés';
        }

        // Compétences recherchées
        $required = self::normalize($offer->required_skills ?? []);

        if ($required) {
            $have = self::normalize($candidate->skills ?? []);
            $found = collect($required)->filter(fn ($r) => self::has($have, $r))->count();
            $max += $w['skills'];
            $earned += $w['skills'] * $found / count($required);
            $details[] = "Compétences : {$found}/".count($required);
        }

        if ($max <= 0) {
            return ['score' => null, 'details' => []];
        }

        return ['score' => (int) round($earned / $max * 100), 'details' => $details];
    }

    /** @return array{key: string, label: string, class: string} */
    public static function band(?int $score): array
    {
        if ($score === null) {
            return ['key' => 'none', 'label' => '—', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200'];
        }
        if ($score >= (int) config('careers.shortlist_threshold')) {
            return ['key' => 'match', 'label' => 'Correspond', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200'];
        }
        if ($score >= (int) config('careers.review_threshold')) {
            return ['key' => 'review', 'label' => 'À examiner', 'class' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200'];
        }

        return ['key' => 'low', 'label' => 'Correspond peu', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200'];
    }

    /** @return array<int, string> compétences en minuscules, sans accents ni doublons */
    public static function normalize(array $skills): array
    {
        return collect($skills)
            ->map(fn ($s) => (string) Str::of((string) $s)->ascii()->lower()->trim())
            ->filter()->unique()->values()->all();
    }

    private static function has(array $candidateSkills, string $required): bool
    {
        foreach ($candidateSkills as $skill) {
            if ($skill === $required
                || (strlen($required) >= 3 && str_contains($skill, $required))
                || (strlen($skill) >= 3 && str_contains($required, $skill))) {
                return true;
            }
        }

        return false;
    }

    private static function years(float|int|string|null $v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',').' an(s)';
    }
}
