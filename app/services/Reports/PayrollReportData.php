<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Lecture SEULE des données des rapports de paie. Chaque requête porte le filtre d'entreprise explicite (comme HrReportEngine :
 * aucune dépendance aux scopes de société). Seuls les mois calculés ou clôturés ont des bulletins.
 */
final class PayrollReportData
{
    /**
     * Totaux par mois (bulletins calculés ou clôturés), après filtre de départements.
     *
     * @param array<int, string> $periods AAAA-MM
     * @param array<int, int> $deptIds vide = tous les départements
     *
     * @return array<string, array{status: string, n: int, gross: int, net_to_pay: int, employer_cost: int, employer_total: int}> trié par mois
     */
    public static function months(int $companyId, array $periods, array $deptIds = []): array
    {
        if ($periods === []) {
            return [];
        }

        $statuses = DB::table('payroll_periods')->where('company_id', $companyId)->whereIn('period', $periods)->whereIn('status', ['calculated', 'closed'])->pluck('status', 'period')->all();

        if ($statuses === []) {
            return [];
        }

        $rows = DB::table('payslips')->where('company_id', $companyId)->whereIn('period', array_keys($statuses))
            ->when($deptIds !== [], fn ($q) => $q->whereIn('department_id', $deptIds))
            ->selectRaw('period, COUNT(*) as n, COALESCE(SUM(gross), 0) as gross, COALESCE(SUM(net_to_pay), 0) as net_to_pay, COALESCE(SUM(employer_cost), 0) as employer_cost, COALESCE(SUM(employer_total), 0) as employer_total')
            ->groupBy('period')->get();

        $out = [];

        foreach ($rows as $r) {
            $out[(string) $r->period] = [
                'status' => (string) $statuses[(string) $r->period], 'n' => (int) $r->n, 'gross' => (int) $r->gross,
                'net_to_pay' => (int) $r->net_to_pay, 'employer_cost' => (int) $r->employer_cost, 'employer_total' => (int) $r->employer_total,
            ];
        }

        ksort($out);

        return $out;
    }

    /**
     * Une ligne par service sur un ensemble de mois : effectif distinct et sommes. Le nom est celui figé sur le bulletin.
     *
     * @param array<int, string> $periods
     * @param array<int, int> $deptIds
     *
     * @return array<int, array{name: ?string, count: int, net_to_pay: int, gross: int, employer_cost: int}>
     */
    public static function departments(int $companyId, array $periods, array $deptIds = []): array
    {
        if ($periods === []) {
            return [];
        }

        return DB::table('payslips')->where('company_id', $companyId)->whereIn('period', $periods)
            ->when($deptIds !== [], fn ($q) => $q->whereIn('department_id', $deptIds))
            ->selectRaw('department_name, COUNT(DISTINCT employee_id) as n, COALESCE(SUM(gross), 0) as gross, COALESCE(SUM(net_to_pay), 0) as net_to_pay, COALESCE(SUM(employer_cost), 0) as employer_cost')
            ->groupBy('department_name')->get()
            ->map(fn ($r) => ['name' => $r->department_name, 'count' => (int) $r->n, 'net_to_pay' => (int) $r->net_to_pay, 'gross' => (int) $r->gross, 'employer_cost' => (int) $r->employer_cost])
            ->all();
    }

    /** Coût employeur de chaque employé pour un mois. @return array<int, int> employee_id => coût */
    public static function employeeCosts(int $companyId, string $period, array $deptIds = []): array
    {
        $out = [];

        foreach (DB::table('payslips')->where('company_id', $companyId)->where('period', $period)
            ->when($deptIds !== [], fn ($q) => $q->whereIn('department_id', $deptIds))->get(['employee_id', 'employer_cost']) as $r) {
            $out[(int) $r->employee_id] = (int) $r->employer_cost;
        }

        return $out;
    }

    /** Premier mois pour lequel des bulletins existent, ou null. */
    public static function firstPeriod(int $companyId): ?string
    {
        $p = DB::table('payslips')->where('company_id', $companyId)->min('period');

        return $p === null ? null : (string) $p;
    }

    /** Catégories salariales de l'entreprise. @return array<int, string> id => « code libellé » */
    public static function gradeOptions(int $companyId): array
    {
        $out = [];

        foreach (DB::table('salary_grades as g')->join('salary_grids as r', 'r.id', '=', 'g.salary_grid_id')->where('r.company_id', $companyId)
            ->orderBy('r.name')->orderBy('g.sort')->orderBy('g.id')->get(['g.id', 'g.code', 'g.label']) as $g) {
            $out[(int) $g->id] = trim($g->code . ' ' . $g->label);
        }

        return $out;
    }

    /**
     * Salaires en vigueur à une date, classés par rapport à la catégorie du poste. Aucun nom, aucun montant individuel n'est renvoyé.
     *
     * @param array<int, int> $deptIds
     * @param array<int, int> $gradeIds filtre sur la catégorie du poste ; vide = toutes
     *
     * @return array{rows: array<int, array{department: ?string, category: ?string, status: string}>, no_salary: int}
     */
    public static function grid(int $companyId, string $asOf, array $deptIds = [], array $gradeIds = []): array
    {
        $employees = DB::table('employees')->where('company_id', $companyId)->whereNull('deleted_at')->where('status', 'active')
            ->when($deptIds !== [], fn ($q) => $q->whereIn('department_id', $deptIds))->get(['id', 'department_id', 'position_id']);

        $departments = DB::table('departments')->where('company_id', $companyId)->pluck('name', 'id')->all();
        $positionGrade = DB::table('positions')->where('company_id', $companyId)->pluck('salary_grade_id', 'id')->all();
        $gradeLabels = self::gradeOptions($companyId);

        $steps = DB::table('salary_steps as s')->join('salary_grades as g', 'g.id', '=', 's.salary_grade_id')->join('salary_grids as r', 'r.id', '=', 'g.salary_grid_id')
            ->where('r.company_id', $companyId)->get(['s.id', 's.salary_grade_id', 's.amount']);

        $stepAmount = [];
        $range = [];

        foreach ($steps as $s) {
            $amount = (int) $s->amount;
            $stepAmount[(int) $s->id] = $amount;
            $g = (int) $s->salary_grade_id;

            if ($amount > 0) {
                $range[$g] = ['min' => min($range[$g]['min'] ?? $amount, $amount), 'max' => max($range[$g]['max'] ?? $amount, $amount)];
            }
        }

        $lines = [];

        foreach (DB::table('employee_salaries')->where('company_id', $companyId)->whereDate('effective_from', '<=', $asOf)
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $asOf))
            ->orderBy('effective_from')->orderBy('id')->get(['employee_id', 'base_amount', 'salary_step_id']) as $l) {
            $lines[(int) $l->employee_id] = $l; // la plus récente l'emporte
        }

        $rows = [];
        $noSalary = 0;

        foreach ($employees as $e) {
            $gradeId = $positionGrade[$e->position_id] ?? null;
            $gradeId = $gradeId === null ? null : (int) $gradeId;

            if ($gradeIds !== [] && ($gradeId === null || ! in_array($gradeId, $gradeIds, true))) {
                continue;
            }

            $line = $lines[(int) $e->id] ?? null;

            if ($line === null) {
                $noSalary++;

                continue;
            }

            $grade = $gradeId !== null && isset($range[$gradeId]) ? $range[$gradeId] : null;
            $stepId = $line->salary_step_id === null ? null : (int) $line->salary_step_id;

            $rows[] = [
                'department' => $departments[$e->department_id] ?? null,
                'category' => $gradeId !== null ? ($gradeLabels[$gradeId] ?? null) : null,
                'status' => GridGapReport::classify(['base' => (int) $line->base_amount, 'step_amount' => $stepId !== null ? ($stepAmount[$stepId] ?? null) : null], $grade),
            ];
        }

        return ['rows' => $rows, 'no_salary' => $noSalary];
    }
}
