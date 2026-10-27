<?php

namespace App\Livewire\Permissions;

use App\Models\PermissionRequest;
use App\Services\PermissionService;
use App\Support\LeaveVisibility;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Permission')]
class PermissionShow extends Component
{
    public PermissionRequest $permission;

    public function mount(PermissionRequest $permission): void
    {
        abort_unless(app(PermissionService::class)->canView(auth()->user(), $permission), 403);
        $this->permission = $permission->load(['employee', 'type', 'document', 'steps.decider']);
    }

    public function cancel(): void
    {
        app(PermissionService::class)->cancel($this->permission, auth()->user());

        $this->permission->refresh()->load(['employee', 'type', 'document', 'steps.decider']);
        $this->dispatch('notify', type: 'success', message: 'Demande annulée.');
    }

    public function render()
    {
        $user = auth()->user();
        $isOwner = $user->employee_id && (int) $user->employee_id === $this->permission->employee_id;
        $isHr = LeaveVisibility::isHr($user, 'absence_permissions');

        return view('livewire.permissions.permission-show', [
            'canCancel' => ($this->permission->status === 'pending' && ($isOwner || $isHr))
                || ($this->permission->status === 'approved' && $isHr && $this->permission->start_date->gt(today())),
        ]);
    }
}
