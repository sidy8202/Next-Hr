<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Services\OvertimeService;
use App\Support\LeaveVisibility;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/** Jours de repos ou fériés travaillés : à compenser par un jour de récupération ou un ajout au solde de congé. */
#[Layout('components.layouts.app')]
#[Title('Jours travaillés')]
class OvertimeIndex extends Component
{
    use AuthorizesResource, WithPagination;

    public $employee_id = '';
    public string $worked_on = '';
    public $days = 1;
    public string $reason = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'overtime');
    }

    /** Employés pour lesquels l'utilisateur peut déclarer (soi-même, son équipe, ou tous selon la portée). */
    #[Computed]
    public function declarable()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'overtime');

        return Employee::where('status', 'active')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('last_name')->get(['id', 'matricule', 'first_name', 'last_name']);
    }

    #[Computed]
    public function records()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'overtime');

        return OvertimeRecord::with('employee:id,matricule,first_name,last_name')
            ->when($ids !== null, fn ($q) => $q->whereIn('employee_id', $ids))
            ->latest('worked_on')->paginate(15);
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'overtime');

        $allowed = $this->declarable->pluck('id')->all();

        $this->validate([
            'employee_id' => ['required', Rule::in($allowed)],
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            'days' => ['required', 'numeric', 'between:0.25,1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], ['employee_id' => 'employé', 'worked_on' => 'jour travaillé', 'days' => 'durée']);

        app(OvertimeService::class)->declare(Employee::findOrFail($this->employee_id), $this->worked_on, (float) $this->days, $this->reason);

        $this->reset(['worked_on', 'reason']);
        $this->days = 1;
        unset($this->records);
        $this->dispatch('notify', type: 'success', message: 'Déclaration envoyée à la validation.');
    }

    public function cancel(int $id): void
    {
        $record = OvertimeRecord::findOrFail($id);
        abort_unless(in_array($record->employee_id, $this->declarable->pluck('id')->all(), true), 403);

        app(OvertimeService::class)->cancel($record);

        unset($this->records);
    }

    public function render()
    {
        // Employé unique : présélectionné
        if ($this->employee_id === '' && $this->declarable->count() === 1) {
            $this->employee_id = $this->declarable->first()->id;
        }

        return view('livewire.leaves.overtime-index');
    }
}
