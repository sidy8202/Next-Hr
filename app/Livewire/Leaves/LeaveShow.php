<?php

namespace App\Livewire\Leaves;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Demande de congé')]
class LeaveShow extends Component
{
    public LeaveRequest $leave;

    public function mount(LeaveRequest $leave): void
    {
        abort_unless(app(LeaveService::class)->canView(auth()->user(), $leave), 403);
        $this->leave = $leave->load(['employee', 'type', 'interim', 'document', 'steps.decider']);
    }

    public function cancel(): void
    {
        app(LeaveService::class)->cancel($this->leave, auth()->user());

        $this->leave->refresh()->load(['employee', 'type', 'interim', 'document', 'steps.decider']);
        $this->dispatch('notify', type: 'success', message: 'Demande annulée.');
    }

    public function render()
    {
        $user = auth()->user();
        $isOwner = $user->employee_id && (int) $user->employee_id === $this->leave->employee_id;
        $isHr = \App\Support\LeaveVisibility::isHr($user, 'leave_requests');

        return view('livewire.leaves.leave-show', [
            'canCancel' => ($this->leave->status === 'pending' && ($isOwner || $isHr))
                || ($this->leave->status === 'approved' && $isHr && $this->leave->start_date->gt(today())),
        ]);
    }
}
