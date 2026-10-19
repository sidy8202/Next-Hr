<?php

namespace App\Livewire;

use App\Models\Department;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmployeeQuickCreate extends Component
{
    public bool $open = false;
    public string $first_name = '';
    public string $last_name = '';
    public ?int $department_id = null;
    public ?int $position_id = null;
    public string $hire_date = '';

    public function mount(): void
    {
        $this->hire_date = now()->format('Y-m-d');
    }

    public function save(EmployeeService $service): void
    {
        abort_unless(Auth::user()->canDo('create', 'employees'), 403);
        $cid = Auth::user()->company_id;

        $this->validate([
            'first_name'    => ['required', 'string', 'max:60'],
            'last_name'     => ['required', 'string', 'max:60'],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'position_id'   => ['required', Rule::exists('positions', 'id')->where('company_id', $cid)],
            'hire_date'     => ['required', 'date'],
        ]);

        $department = Department::findOrFail($this->department_id);

        $result = $service->enrollBatch(
            $department,
            ['hire_date' => $this->hire_date, 'contract_type' => 'CDI'],
            [[
                'first_name'  => $this->first_name,
                'last_name'   => $this->last_name,
                'position_id' => $this->position_id,
                'salary'      => 0,
            ]]
        );

        $this->reset(['first_name', 'last_name', 'open']);
        $this->dispatch('employee-created');
        $this->dispatch('notify', type: 'success', message: 'Employé créé avec succès.');
    }

    public function render()
    {
        $cid = Auth::user()->company_id;

        return view('livewire.employee-quick-create', [
            'departments' => Department::where('company_id', $cid)->where('is_active', true)->get(),
            'positions'   => $this->department_id
                ? \App\Models\Position::where('department_id', $this->department_id)->get()
                : [],
        ]);
    }
}
