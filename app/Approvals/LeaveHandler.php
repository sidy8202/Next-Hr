<?php

namespace App\Approvals;

use App\Models\ApprovalStep;
use App\Models\LeaveRequest;
use App\Services\EmployeeNotifier;
use App\Services\LeaveService;
use Illuminate\Database\Eloquent\Model;

class LeaveHandler extends ApprovalHandler
{
    public function __construct(private LeaveService $leaves, private EmployeeNotifier $notifier)
    {
    }

    public function type(): string
    {
        return 'leave';
    }

    public function resource(): string
    {
        return 'leave_requests';
    }

    public function find(int $id): ?Model
    {
        return LeaveRequest::with(['type', 'employee'])->find($id);
    }

    public function employeeId(Model $subject): int
    {
        return $subject->employee_id;
    }

    public function label(Model $subject): string
    {
        return $subject->type?->name.' '.$subject->periodLabel().' ('.rtrim(rtrim(number_format($subject->days, 1, ',', ''), '0'), ',').' j)';
    }

    public function url(Model $subject): string
    {
        return route('leaves.show', $subject->id);
    }

    public function beforeDecide(Model $subject, ApprovalStep $step, string $decision, array $options): void
    {
        if ($decision === 'approve' && $step->kind === 'hr') {
            $this->leaves->assertCanApprove($subject); // le solde est revérifié au moment de la validation finale
        }
    }

    public function onApproved(Model $subject, ApprovalStep $last, array $options): void
    {
        $this->leaves->finalize($subject);
    }

    public function onRejected(Model $subject, ApprovalStep $step): void
    {
        $subject->update(['status' => 'rejected', 'decided_at' => now()]);

        $this->notifier->notifyEmployee(
            $subject->employee_id,
            'Demande de congé refusée',
            'Votre demande de '.mb_strtolower($subject->type?->name).' '.$subject->periodLabel().' a été refusée. Motif : '.$step->comment,
            $this->url($subject)
        );
    }
}
