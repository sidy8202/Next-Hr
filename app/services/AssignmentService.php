<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSite;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Affectations : un employé garde son poste principal et peut occuper, pendant une période, d'autres postes
 * (intérim, détachement, cumul) dans le même département ou un autre, dans la même base ou une autre.
 */
class AssignmentService
{
    public function __construct(private EmployeeNotifier $notifier)
    {
    }

    /** @param array<string, mixed> $d */
    public function create(Employee $employee, array $d): Assignment
    {
        $this->check($employee, $d);

        $assignment = DB::transaction(function () use ($employee, $d) {
            $assignment = Assignment::create([
                'employee_id' => $employee->id,
                'type' => $d['type'],
                'department_id' => (int) $d['department_id'],
                'position_id' => (int) $d['position_id'],
                'work_site_id' => ! empty($d['work_site_id']) ? (int) $d['work_site_id'] : null,
                'reports_to_employee_id' => ! empty($d['reports_to_employee_id']) ? (int) $d['reports_to_employee_id'] : null,
                'start_date' => Carbon::parse($d['start_date'])->toDateString(),
                'end_date' => ! empty($d['end_date']) ? Carbon::parse($d['end_date'])->toDateString() : null,
                'allocation_percent' => ! empty($d['allocation_percent']) ? (int) $d['allocation_percent'] : null,
                'allowance_amount' => isset($d['allowance_amount']) && $d['allowance_amount'] !== '' ? round((float) $d['allowance_amount'], 2) : null,
                'allowance_basis' => $d['allowance_basis'] ?? 'monthly',
                'acts_as_head' => ! empty($d['acts_as_head']),
                'manager_validates' => ! array_key_exists('manager_validates', $d) || ! empty($d['manager_validates']),
                'reason' => trim((string) ($d['reason'] ?? '')) ?: null,
                'created_by' => auth()->id(),
            ]);

            AuditLogger::log('created', 'assignments', $assignment->id, null, [
                'employee_id' => $employee->id, 'type' => $assignment->type, 'department_id' => $assignment->department_id, 'start' => $assignment->start_date->toDateString(),
            ]);

            return $assignment;
        });

        $assignment->load(['department', 'position', 'site']);

        $this->notifier->notifyEmployee($employee, 'Nouvelle affectation',
            $assignment->typeLabel().' : '.($assignment->position?->title ?? 'poste').' — '.$assignment->department?->name
            .($assignment->site ? ' ('.$assignment->site->name.')' : '').', '.$this->period($assignment).'.', route('assignments.index'));

        return $assignment;
    }

    /** Met fin à une affectation (aujourd'hui par défaut). */
    public function end(Assignment $assignment, ?string $date = null): Assignment
    {
        $end = $date ? Carbon::parse($date) : today();

        if ($end->lt($assignment->start_date)) {
            throw ValidationException::withMessages(['assignment' => 'La date de fin précède le début de l’affectation.']);
        }

        $assignment->update(['end_date' => $end->toDateString()]);
        AuditLogger::log('ended', 'assignments', $assignment->id, null, ['end_date' => $end->toDateString()]);

        $this->notifier->notifyEmployee($assignment->employee_id, 'Fin d’affectation', 'Votre affectation ('.$assignment->typeLabel().') prend fin le '.$end->format('d/m/Y').'.', route('assignments.index'));

        return $assignment;
    }

    public function delete(Assignment $assignment): void
    {
        AuditLogger::log('deleted', 'assignments', $assignment->id, ['employee_id' => $assignment->employee_id], null);
        $assignment->delete();
    }

    /** @return Collection<int, Assignment> affectations en cours à une date */
    public function activeFor(int $employeeId, ?Carbon $on = null): Collection
    {
        return Assignment::active($on)->where('employee_id', $employeeId)->with(['department', 'position', 'site'])->get();
    }

    /**
     * Primes d'affectation d'un mois, proratisées au jour (prêtes pour la paie).
     *  - monthly : montant mensuel × jours d'affectation dans le mois ÷ jours du mois ;
     *  - total : montant réparti sur toute la durée de l'affectation (date de fin obligatoire).
     *
     * @return array<int, array{assignment: Assignment, days: int, amount: float, label: string}>
     */
    public function allowanceLines(Employee $employee, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $lines = [];

        $assignments = Assignment::where('employee_id', $employee->id)->whereNotNull('allowance_amount')
            ->whereDate('start_date', '<=', $to)->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from))->get();

        foreach ($assignments as $a) {
            $start = $a->start_date->gt($from) ? $a->start_date->copy() : $from->copy();
            $end = ($a->end_date && $a->end_date->lt($to)) ? $a->end_date->copy() : $to->copy();
            $days = (int) $start->diffInDays($end) + 1;

            if ($a->allowance_basis === 'total' && $a->end_date) {
                $total = (int) $a->start_date->diffInDays($a->end_date) + 1;
                $amount = $a->allowance_amount * $days / $total;
            } else {
                $amount = $a->allowance_amount * $days / $from->daysInMonth;
            }

            $lines[] = ['assignment' => $a, 'days' => $days, 'amount' => round($amount, 2), 'label' => 'Prime '.mb_strtolower($a->typeLabel())." ({$days} j)"];
        }

        return $lines;
    }

    /**
     * Répartition du temps / du coût d'un employé à une date : les affectations en cours prennent leur pourcentage,
     * le poste principal garde le reste (100 % − total). Informatif : sert plus tard aux rapports de coût par département.
     *
     * @return array{primary: int, assigned: int}
     */
    public function costSplit(Employee $employee, ?Carbon $on = null): array
    {
        $assigned = min(100, (int) Assignment::active($on)->where('employee_id', $employee->id)->sum('allocation_percent'));

        return ['primary' => 100 - $assigned, 'assigned' => $assigned];
    }

    private function check(Employee $employee, array $d): void
    {
        $fail = fn (string $m) => throw ValidationException::withMessages(['assignment' => $m]);

        if (! isset(Assignment::TYPES[$d['type'] ?? ''])) {
            $fail('Type d’affectation inconnu.');
        }
        if (empty($d['start_date'])) {
            $fail('La date de début est obligatoire.');
        }

        $start = Carbon::parse($d['start_date']);
        $end = ! empty($d['end_date']) ? Carbon::parse($d['end_date']) : null;

        if ($end && $end->lt($start)) {
            $fail('La date de fin précède la date de début.');
        }
        if (! Department::whereKey((int) ($d['department_id'] ?? 0))->exists()) {
            $fail('Département inconnu.');
        }
        if (! Position::where('department_id', (int) $d['department_id'])->whereKey((int) ($d['position_id'] ?? 0))->exists()) {
            $fail('Ce poste n’appartient pas au département choisi.');
        }
        if (! empty($d['work_site_id']) && ! WorkSite::whereKey((int) $d['work_site_id'])->exists()) {
            $fail('Base inconnue.');
        }
        if (! empty($d['reports_to_employee_id'])) {
            if ((int) $d['reports_to_employee_id'] === $employee->id) {
                $fail('Un employé ne peut pas être son propre supérieur.');
            }
            if (! Employee::whereKey((int) $d['reports_to_employee_id'])->exists()) {
                $fail('Supérieur inconnu.');
            }
        }
        if (($d['allowance_basis'] ?? 'monthly') === 'total' && ! empty($d['allowance_amount']) && ! $end) {
            $fail('Une prime « répartie sur la durée » exige une date de fin.');
        }
        if (! empty($d['allocation_percent']) && ((int) $d['allocation_percent'] < 1 || (int) $d['allocation_percent'] > 100)) {
            $fail('La répartition doit être comprise entre 1 et 100 %.');
        }

        if (! empty($d['allocation_percent'])) {
            $others = (int) Assignment::where('employee_id', $employee->id)
                ->whereDate('start_date', '<=', $end ?? Carbon::create(9999, 12, 31))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start))->sum('allocation_percent');

            if ($others + (int) $d['allocation_percent'] > 100) {
                $fail("La répartition totale dépasserait 100 % (déjà affecté : {$others} % sur cette période).");
            }
        }

        $duplicate = Assignment::where('employee_id', $employee->id)->where('position_id', (int) $d['position_id'])
            ->where('work_site_id', ! empty($d['work_site_id']) ? (int) $d['work_site_id'] : null)
            ->whereDate('start_date', '<=', $end ?? Carbon::create(9999, 12, 31))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start))->exists();

        if ($duplicate) {
            $fail('Cet employé a déjà cette affectation (même poste, même base) sur cette période.');
        }
    }

    private function period(Assignment $a): string
    {
        return $a->end_date
            ? 'du '.$a->start_date->format('d/m/Y').' au '.$a->end_date->format('d/m/Y')
            : 'à partir du '.$a->start_date->format('d/m/Y');
    }
}
