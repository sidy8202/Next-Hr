<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\LeaveVisibility;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Demandes de congé')]
class LeaveIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'annee')]
    public string $year = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'leave_requests');
        LeaveType::ensureDefaults(auth()->user()->company_id);
    }

    public function updated($name): void
    {
        if (in_array($name, ['status', 'type', 'search', 'year'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function types()
    {
        return LeaveType::orderBy('sort')->get(['id', 'name']);
    }

    #[Computed]
    public function requests()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'leave_requests');
        $term = trim($this->search);

        return LeaveRequest::with(['employee:id,matricule,first_name,last_name,department_id', 'type'])
            ->when($ids !== null, fn ($q) => $q->whereIn('employee_id', $ids))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== '', fn ($q) => $q->where('leave_type_id', (int) $this->type))
            ->when($this->year !== '', fn ($q) => $q->whereYear('start_date', (int) $this->year))
            ->when($term !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e
                ->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('matricule', 'like', "{$term}%")))
            ->latest('start_date')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.leaves.leave-index');
    }
}
