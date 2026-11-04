<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExplanationCase;
use App\Models\LeaveRequest;
use App\Models\Mission;
use App\Models\MissionParticipant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Données des dashboards d'une entreprise. Tout est limité à l'entreprise de l'utilisateur par les scopes de société des modèles.
 * Chaque bloc est protégé : si un module n'est pas encore installé ou si une colonne diffère, le bloc s'affiche vide
 * au lieu de faire planter tout le tableau de bord.
 */
class CompanyDashboardData
{
    private CarbonImmutable $today;

    public function __construct(private User $user)
    {
        $this->today = CarbonImmutable::today();
    }

    private function safe(callable $fn, mixed $default = null): mixed
    {
        try {
            return $fn();
        } catch (Throwable) {
            return $default;
        }
    }

    private function active(?int $dept = null)
    {
        return Employee::where('status', 'active')->when($dept, fn ($q) => $q->where('department_id', $dept));
    }

    private function names(Collection $ids, int $limit = 12): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        return Employee::whereIn('id', $ids->all())->orderBy('last_name')->limit($limit)->get(['id', 'first_name', 'last_name'])->map(fn ($e) => $e->full_name)->all();
    }

    /* ───────────── Qui est là aujourd'hui ───────────── */

    public function presence(?int $dept = null): array
    {
        $ids = $this->active($dept)->pluck('id');

        $leave = $this->safe(fn () => LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', $this->today)->whereDate('end_date', '>=', $this->today)
            ->whereIn('employee_id', $ids)->pluck('employee_id')->unique()->values(), collect());

        // Statut « en mission » : participants des missions APPROUVÉES dont les dates couvrent aujourd'hui.
        $mission = $this->safe(fn () => MissionParticipant::whereHas('mission', fn ($m) => $m->ongoingOn($this->today))
            ->whereIn('employee_id', $ids)->pluck('employee_id')->unique()->values(), collect())->diff($leave)->values();

        $away = $leave->merge($mission)->unique()->count();

        return [
            'active' => $ids->count(), 'leave' => $leave->count(), 'mission' => $mission->count(), 'present' => max(0, $ids->count() - $away),
            'leave_ids' => $leave, 'mission_ids' => $mission,
        ];
    }

    private function leaveDaysByMonth(int $months, ?int $dept = null): array
    {
        $ids = $this->active($dept)->pluck('id');
        $start = $this->today->startOfMonth()->subMonthsNoOverflow($months - 1);
        $rows = $this->safe(fn () => LeaveRequest::where('status', 'approved')->whereDate('start_date', '>=', $start)->whereIn('employee_id', $ids)->get(['start_date', 'days']), collect());
        $labels = $values = [];

        for ($i = 0; $i < $months; $i++) {
            $m = $start->addMonthsNoOverflow($i);
            $labels[] = $m->translatedFormat('M');
            $values[] = (int) round($rows->filter(fn ($r) => $r->start_date->isSameMonth($m))->sum('days'));
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** Congés et missions qui commencent dans les 14 prochains jours. */
    private function upcoming(?int $dept = null, bool $withNames = true): array
    {
        $ids = $this->active($dept)->pluck('id');
        $to = $this->today->addDays(14);
        $out = [];

        $leaves = $this->safe(fn () => LeaveRequest::where('status', 'approved')->whereDate('start_date', '>=', $this->today)->whereDate('start_date', '<=', $to)
            ->whereIn('employee_id', $ids)->orderBy('start_date')->limit(8)->get(['employee_id', 'start_date', 'end_date', 'days']), collect());
        $emp = Employee::whereIn('id', $leaves->pluck('employee_id'))->get(['id', 'first_name', 'last_name'])->keyBy('id');

        foreach ($leaves as $l) {
            $out[] = ['date' => $l->start_date, 'kind' => 'Congé', 'tone' => 'sky', 'label' => $withNames ? ($emp[$l->employee_id]->full_name ?? '—') : 'Un collègue', 'detail' => 'jusqu’au '.$l->end_date->format('d/m').' · '.(float) $l->days.' j'];
        }

        $missions = $this->safe(fn () => Mission::where('status', 'approved')->whereDate('start_date', '>=', $this->today)->whereDate('start_date', '<=', $to)
            ->whereHas('participants', fn ($p) => $p->whereIn('employee_id', $ids))->withCount(['participants' => fn ($p) => $p->whereIn('employee_id', $ids)])->orderBy('start_date')->limit(8)->get(), collect());

        foreach ($missions as $m) {
            $out[] = ['date' => $m->start_date, 'kind' => 'Mission', 'tone' => 'violet', 'label' => $m->destination_zone, 'detail' => $m->participants_count.' participant(s) · retour le '.$m->end_date->format('d/m')];
        }

        usort($out, fn ($a, $b) => $a['date'] <=> $b['date']);

        return array_slice($out, 0, 10);
    }

    /* ───────────── Direction et RH ───────────── */

    public function hr(): array
    {
        $u = $this->user;
        $presence = $this->presence();
        $depts = $this->safe(fn () => Department::pluck('name', 'id'), collect());

        $byDept = $this->active()->selectRaw('department_id, count(*) as c')->groupBy('department_id')->pluck('c', 'department_id')->sortDesc();

        // Effectif par département × ancienneté (tableau croisé coloré)
        $buckets = ['< 1 an' => [0, 1], '1 à 3 ans' => [1, 3], '3 à 5 ans' => [3, 5], '5 à 10 ans' => [5, 10], '10 ans et +' => [10, 999]];
        $emps = $this->active()->get(['id', 'department_id', 'hire_date']);
        $matrix = [];

        foreach ($byDept->keys()->take(10) as $deptId) {
            $row = array_fill(0, count($buckets), 0);

            foreach ($emps->where('department_id', $deptId) as $e) {
                $years = $e->hire_date ? $e->hire_date->diffInYears($this->today) : 0;

                foreach (array_values($buckets) as $i => [$a, $b]) {
                    if ($years >= $a && $years < $b) { $row[$i]++; break; }
                }
            }

            $matrix[$depts[$deptId] ?? 'Sans département'] = $row;
        }

        // Embauches par mois
        $start = $this->today->startOfMonth()->subMonthsNoOverflow(11);
        $hires = Employee::whereNotNull('hire_date')->whereDate('hire_date', '>=', $start)->get(['hire_date']);
        $hLabels = $hValues = [];

        for ($i = 0; $i < 12; $i++) {
            $m = $start->addMonthsNoOverflow($i);
            $hLabels[] = $m->translatedFormat('M');
            $hValues[] = $hires->filter(fn ($e) => $e->hire_date->isSameMonth($m))->count();
        }

        // À traiter : uniquement ce que l'utilisateur a le droit de traiter
        $todo = [];
        $add = function (string $label, int $count, string $route, string $tone) use (&$todo) {
            if ($count > 0 && \Illuminate\Support\Facades\Route::has($route)) {
                $todo[] = ['label' => $label, 'count' => $count, 'url' => route($route), 'tone' => $tone];
            }
        };

        if ($u->canDo('read', 'leave_requests')) {
            $add('Demandes de congé à valider', (int) $this->safe(fn () => LeaveRequest::where('status', 'pending')->count(), 0), 'leaves.inbox', 'amber');
        }

        if ($u->canDo('read', 'explanation_letters')) {
            $add('Demandes d’explication sans réponse', (int) $this->safe(fn () => ExplanationCase::where('status', 'awaiting_response')->count(), 0), 'explanations.index', 'amber');
            $add('… dont délai dépassé', (int) $this->safe(fn () => ExplanationCase::where('status', 'awaiting_response')->whereDate('response_deadline', '<', $this->today)->count(), 0), 'explanations.index', 'rose');
        }

        $ms = app(MissionService::class);

        if ($ms->isHr($u)) {
            $add('Missions à vérifier (RH)', (int) $this->safe(fn () => Mission::where('status', 'submitted')->count(), 0), 'missions.index', 'amber');
        }

        if ($ms->isDg($u)) {
            $add('Missions à approuver (DG)', (int) $this->safe(fn () => Mission::where('status', 'hr_approved')->count(), 0), 'missions.index', 'amber');
        }

        // Alertes et anniversaires
        $permits = $this->active()->whereNotNull('work_permit_expires_at')->whereDate('work_permit_expires_at', '>=', $this->today)->whereDate('work_permit_expires_at', '<=', $this->today->addDays(60))
            ->orderBy('work_permit_expires_at')->limit(6)->get(['first_name', 'last_name', 'work_permit_expires_at'])
            ->map(fn ($e) => ['label' => $e->full_name, 'detail' => 'permis de travail expire le '.$e->work_permit_expires_at->format('d/m/Y')])->all();

        $birthdays = $this->active()->whereNotNull('birth_date')->whereMonth('birth_date', $this->today->month)->get(['first_name', 'last_name', 'birth_date'])
            ->sortBy(fn ($e) => $e->birth_date->day)->take(8)
            ->map(fn ($e) => ['label' => $e->full_name, 'detail' => $e->birth_date->day.' '.$this->today->translatedFormat('F'), 'today' => $e->birth_date->day === $this->today->day])->values()->all();

        return [
            'presence' => $presence,
            'hires30' => $this->active()->whereDate('hire_date', '>=', $this->today->subDays(30))->count(),
            'by_dept' => $byDept->take(8)->map(fn ($c, $id) => ['label' => $depts[$id] ?? 'Sans département', 'value' => (int) $c])->values()->all(),
            'matrix' => $matrix, 'matrix_cols' => array_keys($buckets),
            'hires' => ['labels' => $hLabels, 'values' => $hValues],
            'leave_days' => $this->leaveDaysByMonth(6),
            'todo' => $todo, 'todo_total' => array_sum(array_column(array_filter($todo, fn ($t) => $t['tone'] !== 'rose'), 'count')),
            'upcoming' => $this->upcoming(),
            'permits' => $permits, 'birthdays' => $birthdays,
            'on_mission_names' => $this->names($presence['mission_ids']),
            'on_leave_names' => $this->names($presence['leave_ids']),
        ];
    }

    /* ───────────── Département ───────────── */

    public function department(): ?array
    {
        $u = $this->user;
        $emp = $u->employee_id ? Employee::find($u->employee_id, ['id', 'department_id']) : null;

        if (! $emp || ! $emp->department_id) {
            return null;
        }

        $showNames = $u->canDo('read', 'employees');
        $presence = $this->presence($emp->department_id);

        return [
            'name' => Department::whereKey($emp->department_id)->value('name') ?? 'Mon département',
            'presence' => $presence, 'show_names' => $showNames,
            'upcoming' => $this->upcoming($emp->department_id, $showNames),
            'leave_days' => $this->leaveDaysByMonth(6, $emp->department_id),
            'on_mission_names' => $showNames ? $this->names($presence['mission_ids']) : [],
            'on_leave_names' => $showNames ? $this->names($presence['leave_ids']) : [],
            'pending' => $u->canDo('update', 'leave_requests') ? (int) $this->safe(fn () => LeaveRequest::where('status', 'pending')->whereIn('employee_id', $this->active($emp->department_id)->pluck('id'))->count(), 0) : null,
        ];
    }

    /* ───────────── Mon espace ───────────── */

    public function self(): ?array
    {
        $id = $this->user->employee_id;

        if (! $id) {
            return null;
        }

        $mine = $this->safe(fn () => LeaveRequest::where('employee_id', $id)->latest('id')->limit(5)->get(['id', 'start_date', 'end_date', 'days', 'status']), collect());
        $nextLeave = $this->safe(fn () => LeaveRequest::where('employee_id', $id)->where('status', 'approved')->whereDate('end_date', '>=', $this->today)->orderBy('start_date')->first(['start_date', 'end_date', 'days']));
        $nextMission = $this->safe(fn () => Mission::where('status', 'approved')->whereDate('end_date', '>=', $this->today)->whereHas('participants', fn ($p) => $p->where('employee_id', $id))->orderBy('start_date')->first());
        $emp = Employee::with(['department:id,name', 'position:id,title'])->find($id);

        return [
            'employee' => $emp, 'mine' => $mine, 'next_leave' => $nextLeave, 'next_mission' => $nextMission,
            'pending' => $mine->where('status', 'pending')->count(),
            'on_mission_now' => $nextMission && $nextMission->start_date->lte($this->today),
            'tenure_years' => $emp?->hire_date ? $emp->hire_date->diffInYears($this->today) : null,
        ];
    }

    /* ───────────── Paie (à partir des salaires de base des contrats) ───────────── */

    public function payroll(): array
    {
        $emps = $this->active()->get(['id', 'department_id'])->keyBy('id');
        $depts = $this->safe(fn () => Department::pluck('name', 'id'), collect());

        $rows = $this->safe(function () use ($emps) {
            $latest = Contract::query()->selectRaw('max(id) as id')->groupBy('employee_id');

            return Contract::whereIn('id', $latest)->get(['id', 'employee_id', 'base_salary'])
                ->filter(fn ($c) => $emps->has($c->employee_id) && $c->base_salary !== null && (float) $c->base_salary > 0);
        });

        if ($rows === null) {
            return ['available' => false];
        }

        $total = (int) round($rows->sum(fn ($c) => (float) $c->base_salary));
        $bands = ['Moins de 150 000' => [0, 150000], '150 000 à 300 000' => [150000, 300000], '300 000 à 500 000' => [300000, 500000], '500 000 à 1 000 000' => [500000, 1000000], '1 000 000 et plus' => [1000000, PHP_INT_MAX]];
        $byDept = $rows->groupBy(fn ($c) => $emps[$c->employee_id]->department_id)->map(fn ($g, $id) => ['label' => $depts[$id] ?? 'Sans département', 'value' => (int) round($g->sum(fn ($c) => (float) $c->base_salary)), 'sub' => $g->count().' salarié(s)'])->sortByDesc('value')->take(8)->values()->all();

        return [
            'available' => $rows->count() > 0, 'total' => $total, 'count' => $rows->count(), 'avg' => $rows->count() ? intdiv($total, $rows->count()) : 0,
            'missing' => max(0, $emps->count() - $rows->count()), 'active' => $emps->count(), 'by_dept' => $byDept,
            'bands' => ['labels' => array_keys($bands), 'values' => array_values(array_map(fn ($b) => $rows->filter(fn ($c) => (float) $c->base_salary >= $b[0] && (float) $c->base_salary < $b[1])->count(), $bands))],
        ];
    }
}
