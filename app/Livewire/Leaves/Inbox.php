<?php

namespace App\Livewire\Leaves;

use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use App\Services\ApprovalService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Demandes à valider par l'utilisateur connecté : chef de département, intérimaire ou ressources humaines. */
#[Layout('components.layouts.app')]
#[Title('Demandes à valider')]
class Inbox extends Component
{
    /** @var array<int, string> */
    public array $comments = [];

    /** @var array<int, string> */
    public array $compensation = [];

    /** @var array<int, string|float> */
    public array $credited = [];

    #[Computed]
    public function items()
    {
        return app(ApprovalService::class)->pendingFor(auth()->user());
    }

    /** Collègues du même département déjà en congé (ou demandé) sur la période : aide la décision du chef. */
    public function overlaps(LeaveRequest $leave): int
    {
        $employee = $leave->employee;

        if (! $employee?->department_id) {
            return 0;
        }

        return LeaveRequest::where('id', '!=', $leave->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $leave->end_date)->whereDate('end_date', '>=', $leave->start_date)
            ->whereHas('employee', fn ($q) => $q->where('department_id', $employee->department_id)->where('id', '!=', $employee->id))
            ->count();
    }

    public function decide(int $stepId, string $decision): void
    {
        app(ApprovalService::class)->decide(
            $stepId,
            auth()->user(),
            $decision,
            $this->comments[$stepId] ?? null,
            [
                'compensation' => $this->compensation[$stepId] ?? null,
                'credited_days' => $this->credited[$stepId] ?? null,
            ]
        );

        unset($this->comments[$stepId], $this->compensation[$stepId], $this->credited[$stepId], $this->items);
        $this->dispatch('notify', type: 'success', message: $decision === 'approve' ? 'Demande validée.' : 'Demande refusée.');
    }

    public function render()
    {
        return view('livewire.leaves.inbox');
    }
}
