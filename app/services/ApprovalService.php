<?php

namespace App\Services;

use App\Approvals\ApprovalHandler;
use App\Models\ApprovalStep;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\LeaveVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Moteur de validation commun : chef de département (ou son intérimaire), puis ressources humaines.
 * Personne ne valide jamais sa propre demande.
 */
class ApprovalService
{
    public function __construct(private EmployeeNotifier $notifier)
    {
    }

    public function handler(string $type): ApprovalHandler
    {
        $class = config("approvals.handlers.{$type}");
        abort_unless($class, 404);

        return app($class);
    }

    /** Crée les étapes d'une demande. L'étape « chef » est sautée si l'employé n'a pas de chef (il dirige son département). */
    public function start(string $type, Model $subject): void
    {
        $handler = $this->handler($type);
        $employee = Employee::findOrFail($handler->employeeId($subject));
        $position = 1;

        foreach ($handler->chain($subject) as $kind) {
            $skip = $kind === 'manager' && ! $this->managerOf($employee);

            ApprovalStep::create([
                'approvable_type' => $type,
                'approvable_id' => $subject->getKey(),
                'position' => $position++,
                'kind' => $kind,
                'status' => $skip ? 'skipped' : 'pending',
                'comment' => $skip ? 'Pas de chef de département : étape sautée.' : null,
                'decided_at' => $skip ? now() : null,
            ]);
        }
    }

    // ---------- Qui peut valider ? ----------

    public function managerOf(Employee $employee): ?Employee
    {
        $id = $employee->manager_id;

        if (! $id && $employee->department_id) {
            $id = Department::whereKey($employee->department_id)->value('head_id');
        }

        return ($id && (int) $id !== $employee->id) ? Employee::find($id) : null;
    }

    /**
     * Le chef et ses intérimaires du moment.
     *
     * @return array{manager: ?Employee, delegates: array<int, int>}
     */
    public function managerSide(Employee $employee): array
    {
        $manager = $this->managerOf($employee);

        if (! $manager) {
            return ['manager' => null, 'delegates' => []];
        }

        // 1) L'intérimaire désigné dans le congé en cours du chef
        $delegates = LeaveRequest::where('employee_id', $manager->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->whereNotNull('interim_employee_id')->pluck('interim_employee_id')->map(fn ($i) => (int) $i)->all();

        // 2) Le module « Délégations » existant, si ses colonnes correspondent à config/approvals.php
        $delegates = array_merge($delegates, $this->configuredDelegates($manager));

        // 3) Les affectations : qui fait fonction de chef du département, et le supérieur de l'affectation de l'employé
        $delegates = array_merge($delegates, $this->assignmentApprovers($employee));

        return ['manager' => $manager, 'delegates' => array_values(array_unique($delegates))];
    }

    /** Qui fait fonction de chef dans le département de l'employé, et son supérieur d'affectation (s'il peut valider). */
    private function assignmentApprovers(Employee $employee): array
    {
        $acting = Assignment::active()->where('acts_as_head', true)->where('department_id', $employee->department_id)->pluck('employee_id');
        $managers = Assignment::active()->where('employee_id', $employee->id)->where('manager_validates', true)
            ->whereNotNull('reports_to_employee_id')->pluck('reports_to_employee_id');

        return $acting->merge($managers)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function configuredDelegates(Employee $manager): array
    {
        $cfg = config('approvals.delegation');

        if (! ($cfg['enabled'] ?? false) || ! class_exists($cfg['model'])) {
            return [];
        }

        try {
            return $cfg['model']::query()
                ->when($cfg['company'] ?? null, fn ($q, $column) => $q->where($column, $manager->company_id))
                ->where($cfg['delegator'], $manager->id)
                ->whereDate($cfg['from'], '<=', today())
                ->where(fn ($q) => $q->whereNull($cfg['to'])->orWhereDate($cfg['to'], '>=', today()))
                ->pluck($cfg['delegate'])->map(fn ($i) => (int) $i)->all();
        } catch (Throwable $e) {
            report($e); // colonnes différentes : la source est ignorée, la RH peut valider à la place

            return [];
        }
    }

    /** Capacité dans laquelle l'utilisateur peut décider cette étape, ou null. */
    public function actingCapacity(User $user, ApprovalStep $step, ApprovalHandler $handler, Model $subject): ?string
    {
        if ($step->status !== 'pending' || ! $this->isCurrent($step)) {
            return null;
        }

        $requester = Employee::find($handler->employeeId($subject));

        if (! $requester || ($user->employee_id && (int) $user->employee_id === $requester->id)) {
            return null; // jamais sa propre demande
        }

        $hr = LeaveVisibility::isHr($user, $handler->resource());

        if ($step->kind === 'hr') {
            return $hr ? 'hr' : null;
        }

        $side = $this->managerSide($requester);

        if ($user->employee_id && $side['manager'] && (int) $user->employee_id === $side['manager']->id) {
            return 'manager';
        }
        if ($user->employee_id && in_array((int) $user->employee_id, $side['delegates'], true)) {
            return 'delegate';
        }

        return ($hr && config('approvals.hr_can_override_manager')) ? 'hr_override' : null;
    }

    public function isCurrent(ApprovalStep $step): bool
    {
        return ! ApprovalStep::where('approvable_type', $step->approvable_type)
            ->where('approvable_id', $step->approvable_id)
            ->where('position', '<', $step->position)
            ->where('status', 'pending')->exists();
    }

    /** @return Collection<int, array{step: ApprovalStep, subject: Model, handler: ApprovalHandler, capacity: string}> */
    public function pendingFor(User $user): Collection
    {
        return ApprovalStep::where('status', 'pending')->orderBy('created_at')->get()
            ->map(function (ApprovalStep $step) use ($user) {
                $handler = $this->handler($step->approvable_type);
                $subject = $handler->find($step->approvable_id);
                $capacity = $subject ? $this->actingCapacity($user, $step, $handler, $subject) : null;

                return $capacity ? ['step' => $step, 'subject' => $subject, 'handler' => $handler, 'capacity' => $capacity] : null;
            })
            ->filter()->values();
    }

    // ---------- Décider ----------

    public function decide(int $stepId, User $user, string $decision, ?string $comment = null, array $options = []): ApprovalStep
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 422);

        return DB::transaction(function () use ($stepId, $user, $decision, $comment, $options) {
            $step = ApprovalStep::whereKey($stepId)->lockForUpdate()->firstOrFail();
            $handler = $this->handler($step->approvable_type);
            $subject = $handler->find($step->approvable_id);
            abort_unless($subject, 404);

            if ($step->status !== 'pending' || ! $this->isCurrent($step)) {
                throw ValidationException::withMessages(['decide' => 'Cette demande a déjà été traitée.']);
            }

            $capacity = $this->actingCapacity($user, $step, $handler, $subject);
            abort_unless($capacity, 403);

            $comment = trim((string) $comment);

            if ($decision === 'reject' && mb_strlen($comment) < 3) {
                throw ValidationException::withMessages(['decide' => 'Indiquez le motif du refus.']);
            }

            $handler->beforeDecide($subject, $step, $decision, $options);

            $step->update([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'decided_by' => $user->id,
                'acted_as' => $capacity,
                'comment' => $comment !== '' ? $comment : null,
                'decided_at' => now(),
            ]);

            AuditLogger::log($decision === 'approve' ? 'approved' : 'rejected', 'approvals', $step->id, null, [
                'type' => $step->approvable_type, 'id' => $step->approvable_id, 'kind' => $step->kind, 'acted_as' => $capacity,
            ]);

            if ($decision === 'reject') {
                ApprovalStep::where('approvable_type', $step->approvable_type)->where('approvable_id', $step->approvable_id)
                    ->where('status', 'pending')->update(['status' => 'cancelled']);
                $handler->onRejected($subject, $step);

                return $step;
            }

            $next = ApprovalStep::where('approvable_type', $step->approvable_type)->where('approvable_id', $step->approvable_id)
                ->where('status', 'pending')->orderBy('position')->first();

            if ($next) {
                $this->notifyApprovers($next);
            } else {
                $handler->onApproved($subject, $step, $options);
            }

            return $step;
        });
    }

    /** Prévient ceux qui peuvent traiter l'étape en cours. */
    public function notifyApprovers(ApprovalStep $step): void
    {
        $handler = $this->handler($step->approvable_type);
        $subject = $handler->find($step->approvable_id);

        if (! $subject) {
            return;
        }

        $requester = Employee::find($handler->employeeId($subject));

        if (! $requester) {
            return;
        }

        if ($step->kind === 'manager') {
            $side = $this->managerSide($requester);
            $ids = array_filter(array_merge([$side['manager']?->id], $side['delegates']));
            $users = User::where('company_id', $requester->company_id)->where('is_active', true)->whereIn('employee_id', $ids)->get();
        } else {
            $users = User::where('company_id', $requester->company_id)->where('is_active', true)->get()
                ->filter(fn (User $u) => LeaveVisibility::isHr($u, $handler->resource()) && (int) $u->employee_id !== $requester->id);
        }

        $this->notifier->notifyUsers($users, 'Demande à valider', $requester->full_name.' : '.$handler->label($subject), $handler->url($subject));
    }
}
