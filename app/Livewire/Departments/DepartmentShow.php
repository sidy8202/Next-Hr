<?php

namespace App\Livewire\Departments;

use App\Livewire\Concerns\AuthorizesResource;
use App\Livewire\Concerns\WithDataTable;
use App\Models\Department;
use App\Models\Employee;
use App\Services\PresenceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Fiche département')]
class DepartmentShow extends Component
{
    use AuthorizesResource, WithDataTable;

    public Department $department;

    #[Url(as: 'onglet')]
    public string $tab = 'members';

    protected function sortableColumns(): array
    {
        return ['matricule', 'last_name', 'hire_date'];
    }

    public function mount(Department $department): void
    {
        $this->authorizeAction('read', 'departments', $department);
        $this->department = $department;
    }

    public function toggleActive(): void
    {
        $this->authorizeAction('update', 'departments', $this->department);
        $this->department->update(['is_active' => ! $this->department->is_active]);
        $this->dispatch('notify', type: 'success', message: $this->department->is_active ? 'Département activé.' : 'Département désactivé.');
    }

    public function render()
    {
        $presence = app(PresenceService::class);
        $user = auth()->user();
        $search = trim($this->search);

        $members = Employee::visibleTo($user)
            ->where('department_id', $this->department->id)
            ->with('position:id,title')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('matricule', 'like', $search.'%')
                ->orWhere('first_name', 'like', $search.'%')->orWhere('last_name', 'like', $search.'%')))
            ->orderBy($this->orderColumn('last_name'), $this->orderDirection())
            ->paginate($this->pageSize());

        return view('livewire.departments.department-show', [
            'head' => $this->department->head_id ? Employee::find($this->department->head_id) : null,
            'summary' => $presence->summaryForDepartment($this->department),
            'members' => $members,
            'statuses' => $presence->statusesFor($members->pluck('id')),
            'positions' => $this->tab === 'positions'
                ? $this->department->positions()->withCount('employees')->orderBy('title')->get()
                : collect(),
        ]);
    }
}
