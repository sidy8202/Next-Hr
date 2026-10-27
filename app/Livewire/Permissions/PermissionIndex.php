<?php

namespace App\Livewire\Permissions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\PermissionRequest;
use App\Models\PermissionType;
use App\Support\LeaveVisibility;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Permissions')]
class PermissionIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'absence_permissions');
        PermissionType::ensureDefaults(auth()->user()->company_id);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'absence_permissions');

        return PermissionRequest::with(['employee:id,matricule,first_name,last_name', 'type'])
            ->when($ids !== null, fn ($q) => $q->whereIn('employee_id', $ids))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest('start_date')->paginate(15);
    }

    public function render()
    {
        return view('livewire.permissions.permission-index');
    }
}
