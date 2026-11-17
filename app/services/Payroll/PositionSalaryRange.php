<?php

namespace App\Services\Payroll;

use App\Models\Position;
use App\Models\SalaryStep;

/**
 * Fourchette salariale d'un poste = « base conventionnelle » : le plus petit et le plus grand échelon de sa catégorie.
 * range() est pure ; les autres méthodes lisent les échelons et mettent à jour salary_min / salary_max des postes concernés.
 *
 * Un poste SANS catégorie garde sa fourchette libre : on n'y touche jamais.
 */
final class PositionSalaryRange
{
    /**
     * @param array<int, int|string|null> $amounts montants des échelons
     *
     * @return ?array{min: int, max: int} null s'il n'y a aucun montant positif
     */
    public static function range(array $amounts): ?array
    {
        $values = [];

        foreach ($amounts as $a) {
            if ($a !== null && is_numeric($a) && (int) $a > 0) {
                $values[] = (int) $a;
            }
        }

        return $values === [] ? null : ['min' => min($values), 'max' => max($values)];
    }

    /** Aligne la fourchette d'un poste sur les échelons de sa catégorie. Sans catégorie : ne fait rien. */
    public static function syncPosition(Position $position): void
    {
        if (! $position->salary_grade_id) {
            return;
        }

        $range = self::range(SalaryStep::where('salary_grade_id', $position->salary_grade_id)->pluck('amount')->all());

        $position->forceFill(['salary_min' => $range['min'] ?? null, 'salary_max' => $range['max'] ?? null])->save();
    }

    /** À appeler quand les échelons d'une catégorie changent. @return int nombre de postes mis à jour */
    public static function syncGrade(int $gradeId): int
    {
        $positions = Position::where('salary_grade_id', $gradeId)->get();

        foreach ($positions as $position) {
            self::syncPosition($position);
        }

        return $positions->count();
    }

    /** Resynchronise tous les postes de l'entreprise qui ont une catégorie (rattrapage initial). @return int nombre de postes mis à jour */
    public static function syncAll(int $companyId): int
    {
        $positions = Position::where('company_id', $companyId)->whereNotNull('salary_grade_id')->get();

        if ($positions->isEmpty()) {
            return 0;
        }

        $amounts = SalaryStep::whereIn('salary_grade_id', $positions->pluck('salary_grade_id')->unique())->get(['salary_grade_id', 'amount'])->groupBy('salary_grade_id');

        foreach ($positions as $position) {
            $range = self::range($amounts->get($position->salary_grade_id, collect())->pluck('amount')->all());
            $position->forceFill(['salary_min' => $range['min'] ?? null, 'salary_max' => $range['max'] ?? null])->save();
        }

        return $positions->count();
    }
}
