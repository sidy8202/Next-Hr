<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Absence;
use App\Models\Employee;
use App\Services\AbsenceService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/** Absences : une absence non justifiée est retirée du solde de congé (réversible si elle est justifiée ensuite). */
#[Layout('components.layouts.app')]
#[Title('Absences')]
class AbsenceIndex extends Component
{
    use AuthorizesResource, WithPagination;

    public $employee_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $justified = false;
    public string $reason = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'absences');
    }

    #[Computed]
    public function employees()
    {
        return Employee::where('status', 'active')->orderBy('last_name')->get(['id', 'matricule', 'first_name', 'last_name']);
    }

    #[Computed]
    public function absences()
    {
        return Absence::with('employee:id,matricule,first_name,last_name')->latest('start_date')->paginate(15);
    }

    public function updatedStartDate(): void
    {
        if ($this->start_date && (! $this->end_date || $this->end_date < $this->start_date)) {
            $this->end_date = $this->start_date;
        }
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'absences');

        $this->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', auth()->user()->company_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'justified' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], ['employee_id' => 'employé', 'start_date' => 'date de début', 'end_date' => 'date de fin']);

        app(AbsenceService::class)->record(Employee::findOrFail($this->employee_id), $this->start_date, $this->end_date, $this->justified, $this->reason);

        $this->reset(['employee_id', 'start_date', 'end_date', 'justified', 'reason']);
        unset($this->absences);
        $this->dispatch('notify', type: 'success', message: 'Absence enregistrée.');
    }

    public function justify(int $id): void
    {
        $this->authorizeAction('update', 'absences');

        app(AbsenceService::class)->justify(Absence::findOrFail($id));

        unset($this->absences);
        $this->dispatch('notify', type: 'success', message: 'Absence justifiée : les jours retirés ont été rendus.');
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('delete', 'absences');

        app(AbsenceService::class)->delete(Absence::findOrFail($id));

        unset($this->absences);
        $this->dispatch('notify', type: 'success', message: 'Absence supprimée.');
    }

    public function render()
    {
        return view('livewire.leaves.absence-index');
    }
}
