<?php

namespace App\Livewire\Leaves;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\ApprovalService;
use App\Services\LeaveBalanceService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Mes congés')]
class MyLeaves extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->canDo('read', 'leave_requests') || $user->canDo('create', 'leave_requests'), 403);

        LeaveType::ensureDefaults($user->company_id);

        if ($this->employee) {
            app(LeaveBalanceService::class)->sync($this->employee);
        }
    }

    #[Computed]
    public function employee(): ?Employee
    {
        $id = auth()->user()->employee_id;

        return $id ? Employee::find($id) : null;
    }

    #[Computed]
    public function summary(): array
    {
        $b = app(LeaveBalanceService::class);
        $id = $this->employee->id;

        return [
            'balance' => $b->balance($id),
            'reserved' => $b->reserved($id),
            'available' => $b->available($id),
            'rate' => $b->monthlyRate($this->employee),
            'recovery' => $b->balance($id, 'recovery'),
            'credits' => $b->recoveryCredits($id),
        ];
    }

    #[Computed]
    public function requests()
    {
        return LeaveRequest::with('type')->where('employee_id', $this->employee->id)->latest('start_date')->limit(30)->get();
    }

    #[Computed]
    public function ledger()
    {
        return app(LeaveBalanceService::class)->ledger($this->employee->id, 12);
    }

    #[Computed]
    public function toValidate(): int
    {
        return app(ApprovalService::class)->pendingFor(auth()->user())->count();
    }

    public function render()
    {
        return view('livewire.leaves.my-leaves');
    }
}
