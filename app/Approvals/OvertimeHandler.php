<?php

namespace App\Approvals;

use App\Models\ApprovalStep;
use App\Models\OvertimeRecord;
use App\Services\OvertimeService;
use Illuminate\Database\Eloquent\Model;

class OvertimeHandler extends ApprovalHandler
{
    public function __construct(private OvertimeService $overtime)
    {
    }

    public function type(): string
    {
        return 'overtime';
    }

    public function resource(): string
    {
        return 'overtime';
    }

    public function find(int $id): ?Model
    {
        return OvertimeRecord::with('employee')->find($id);
    }

    public function employeeId(Model $subject): int
    {
        return $subject->employee_id;
    }

    public function label(Model $subject): string
    {
        return 'Jour travaillé le '.$subject->worked_on->format('d/m/Y').' ('.rtrim(rtrim(number_format($subject->days, 2, ',', ''), '0'), ',').' j)';
    }

    public function url(Model $subject): string
    {
        return route('leaves.overtime');
    }

    public function beforeDecide(Model $subject, ApprovalStep $step, string $decision, array $options): void
    {
        if ($decision === 'approve' && $step->kind === 'hr') {
            $this->overtime->assertCompensationChosen($options);
        }
    }

    public function onApproved(Model $subject, ApprovalStep $last, array $options): void
    {
        $this->overtime->finalize($subject, $options);
    }

    public function onRejected(Model $subject, ApprovalStep $step): void
    {
        $this->overtime->reject($subject, $step->comment);
    }
}
