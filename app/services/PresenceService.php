<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statut du jour : « présent » = actif, ni en congé approuvé, ni en mission approuvée / en cours.
 * Les identifiants reçus viennent toujours de requêtes déjà filtrées par entreprise.
 */
class PresenceService
{
    public function today(): CarbonInterface
    {
        return now(auth()->user()?->company?->timezone ?? config('app.timezone'));
    }

    /** @return array{leave: array<int,int>, mission: array<int,int>} */
    public function statusesFor(array|Collection $employeeIds, ?CarbonInterface $day = null): array
    {
        $ids = collect($employeeIds)->map(fn ($i) => (int) $i)->unique()->values();

        if ($ids->isEmpty()) {
            return ['leave' => [], 'mission' => []];
        }

        $date = ($day ?? $this->today())->toDateString();

        $leave = DB::table('leave_requests')
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('employee_id')->unique()->values()->all();

        $mission = DB::table('mission_order_employees as moe')
            ->join('mission_orders as mo', 'mo.id', '=', 'moe.mission_order_id')
            ->whereIn('moe.employee_id', $ids)
            ->whereIn('mo.status', ['approved', 'in_progress'])
            ->whereDate('mo.start_date', '<=', $date)
            ->whereDate('mo.end_date', '>=', $date)
            ->pluck('moe.employee_id')->unique()->values()->all();

        return ['leave' => $leave, 'mission' => $mission];
    }

    /** @return array{total:int, leave:int, mission:int, present:int, non_working:bool} */
    public function summaryForDepartment(Department $department): array
    {
        $ids = Employee::where('department_id', $department->id)->where('status', 'active')->pluck('id');
        $statuses = $this->statusesFor($ids);

        $leave = count($statuses['leave']);
        $mission = count(array_diff($statuses['mission'], $statuses['leave']));
        $total = $ids->count();

        return [
            'total' => $total,
            'leave' => $leave,
            'mission' => $mission,
            'present' => max(0, $total - $leave - $mission),
            'non_working' => $this->isNonWorkingDay(),
        ];
    }

    public function isNonWorkingDay(?CarbonInterface $day = null): bool
    {
        $day ??= $this->today();

        if ($day->isWeekend()) {
            return true;
        }

        $companyId = auth()->user()?->company_id;
        $monthDay = $day->format('m-d');

        return DB::table('holidays')
            ->where('company_id', $companyId)
            ->where(fn ($q) => $q->whereDate('date', $day->toDateString())
                ->orWhere(fn ($q) => $q->where('is_recurring', true)->whereRaw("DATE_FORMAT(`date`, '%m-%d') = ?", [$monthDay])))
            ->exists();
    }
}
