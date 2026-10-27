<?php

namespace App\Approvals;

use App\Models\ApprovalStep;
use App\Models\PermissionRequest;
use App\Services\EmployeeNotifier;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;

class PermissionHandler extends ApprovalHandler
{
    public function __construct(private PermissionService $permissions, private EmployeeNotifier $notifier)
    {
    }

    public function type(): string
    {
        return 'permission';
    }

    public function resource(): string
    {
        return 'absence_permissions';
    }

    public function find(int $id): ?Model
    {
        return PermissionRequest::with(['type', 'employee'])->find($id);
    }

    public function employeeId(Model $subject): int
    {
        return $subject->employee_id;
    }

    public function label(Model $subject): string
    {
        $extra = $subject->deducted_days > 0 ? ', dont '.$this->permissions->fmt($subject->deducted_days).' j déduit(s) du congé' : '';

        return $subject->type?->name.' '.$subject->periodLabel().' ('.$this->permissions->fmt($subject->days).' j'.$extra.')';
    }

    public function url(Model $subject): string
    {
        return route('permissions.show', $subject->id);
    }

    public function beforeDecide(Model $subject, ApprovalStep $step, string $decision, array $options): void
    {
        if ($decision === 'approve' && $step->kind === 'hr') {
            $this->permissions->assertCanApprove($subject);
        }
    }

    public function onApproved(Model $subject, ApprovalStep $last, array $options): void
    {
        $this->permissions->finalize($subject);
    }

    public function onRejected(Model $subject, ApprovalStep $step): void
    {
        $subject->update(['status' => 'rejected', 'decided_at' => now()]);

        $this->notifier->notifyEmployee($subject->employee_id, 'Permission refusée',
            'Votre demande de permission '.$subject->periodLabel().' a été refusée. Motif : '.$step->comment, $this->url($subject));
    }
}
