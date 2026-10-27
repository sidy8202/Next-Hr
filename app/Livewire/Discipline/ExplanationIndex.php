<?php

namespace App\Livewire\Discipline;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\ExplanationCase;
use App\Support\LeaveVisibility;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Demandes d’explication')]
class ExplanationIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'explanation_letters');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cases()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'explanation_letters');

        return ExplanationCase::with('employee:id,matricule,first_name,last_name')
            ->when($ids !== null, fn ($q) => $q->whereIn('employee_id', $ids))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()->paginate(15);
    }

    public function render()
    {
        return view('livewire.discipline.explanation-index');
    }
}
