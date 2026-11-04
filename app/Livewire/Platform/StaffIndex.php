<?php

namespace App\Livewire\Platform;

use App\Models\User;
use App\Services\PlatformAudit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.platform')]
#[Title('Équipe plateforme')]
class StaffIndex extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'support';
    public array $perms = [];
    public bool $is_active = true;
    public ?array $temp = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('staff.manage'), 403);
        $this->applyRole();
    }

    public function updatedRole(): void
    {
        $this->applyRole();
    }

    private function applyRole(): void
    {
        $this->perms = config("platform.roles.{$this->role}.permissions", []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'email', 'temp']);
        $this->role = 'support';
        $this->is_active = true;
        $this->applyRole();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $u = User::where('is_platform_admin', true)->findOrFail($id);
        $this->resetErrorBag();
        $this->temp = null;
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->role = $u->platform_role ?: 'support';
        $this->perms = $u->platformPermissionList();
        $this->is_active = (bool) $u->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->platformCan('staff.manage'), 403);

        $editing = $this->editingId ? User::where('is_platform_admin', true)->findOrFail($this->editingId) : null;
        $isOwner = $editing?->isPlatformOwner();

        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($editing?->id)],
            'role' => [Rule::in(array_diff(array_keys(config('platform.roles')), $isOwner ? [] : ['owner']))],
            'perms' => ['array'],
            'perms.*' => [Rule::in(array_keys(config('platform.permissions')))],
        ], [], ['name' => 'nom', 'email' => 'e-mail']);

        $perms = json_encode(array_values(array_intersect($this->perms, array_keys(config('platform.permissions')))));

        if ($editing) {
            // Le propriétaire ne peut être ni rétrogradé ni désactivé ; seuls son nom et son e-mail se modifient.
            $editing->forceFill($isOwner
                ? ['name' => $this->name, 'email' => $this->email]
                : ['name' => $this->name, 'email' => $this->email, 'platform_role' => $this->role, 'platform_permissions' => $perms, 'is_active' => $this->is_active])->save();

            PlatformAudit::log('staff_updated', "Compte de l’équipe modifié : {$editing->name}", null, ['user_id' => $editing->id]);
            $this->temp = null;
        } else {
            $password = Str::random(9).random_int(10, 99);
            $user = new User();
            $user->forceFill([
                'name' => $this->name, 'email' => $this->email, 'password' => $password, 'company_id' => null, 'is_platform_admin' => true,
                'is_active' => $this->is_active, 'must_change_password' => true, 'platform_role' => $this->role, 'platform_permissions' => $perms,
            ])->save();

            PlatformAudit::log('staff_created', "Compte de l’équipe créé : {$user->name} ({$this->role})", null, ['user_id' => $user->id]);
            $this->temp = ['email' => $user->email, 'password' => $password];
        }

        $this->showForm = false;
        $this->dispatch('notify', type: 'success', message: 'Compte enregistré.');
    }

    public function resetPassword(int $id): void
    {
        $u = User::where('is_platform_admin', true)->findOrFail($id);
        abort_if($u->isPlatformOwner() && $u->id !== auth()->id(), 403);

        $password = Str::random(9).random_int(10, 99);
        $u->forceFill(['password' => $password, 'must_change_password' => true])->save();
        PlatformAudit::log('staff_password_reset', "Mot de passe réinitialisé : {$u->name}", null, ['user_id' => $u->id]);
        $this->temp = ['email' => $u->email, 'password' => $password];
    }

    public function render()
    {
        return view('livewire.platform.staff-index', ['staff' => User::where('is_platform_admin', true)->orderByRaw("platform_role = 'owner' desc")->orderBy('name')->get()]);
    }
}
