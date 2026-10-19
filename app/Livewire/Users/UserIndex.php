<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\AuthorizesResource;
use App\Livewire\Concerns\WithDataTable;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Utilisateurs')]
class UserIndex extends Component
{
    use AuthorizesResource, WithDataTable;

    #[Url(as: 'f')]
    public array $filters = ['status' => ''];

    /** Mot de passe provisoire affiché UNE fois : ['name' => ..., 'email' => ..., 'password' => ...] */
    public ?array $resetInfo = null;

    protected function sortableColumns(): array
    {
        return ['name', 'email', 'is_active', 'created_at'];
    }

    public function mount(): void
    {
        $this->authorizeAction('read', 'users');
    }

    public function openAccess()
    {
        $this->authorizeAction('read', 'permissions');
        $id = (int) (array_values($this->selected)[0] ?? 0);
        abort_unless($this->base()->whereKey($id)->exists(), 404);

        return $this->redirectRoute('access.matrix', ['user' => $id], navigate: true);
    }

    public function setActive(bool $active): void
    {
        $this->authorizeAction('update', 'users');
        $me = auth()->user();
        $users = $this->base()->whereIn('id', $this->selectedIds())->get();
        $skipped = 0;

        DB::transaction(function () use ($users, $active, $me, &$skipped) {
            foreach ($users as $user) {
                // On ne désactive ni soi-même ni le propriétaire.
                if (! $active && ($user->id === $me->id || $user->is_company_owner)) {
                    $skipped++;

                    continue;
                }

                $user->update(['is_active' => $active]);
                AuditLogger::log($active ? 'user_activated' : 'user_deactivated', 'users', $user->id, null, ['email' => $user->email]);
            }
        });

        $this->dispatch('notify', type: $skipped ? 'error' : 'success',
            message: $skipped ? "{$skipped} compte(s) ignoré(s) : vous-même et le propriétaire ne peuvent pas être désactivés." : 'Comptes mis à jour.');
        $this->selected = [];
    }

    public function resetPassword(): void
    {
        $this->authorizeAction('update', 'users');

        abort_unless(count($this->selected) === 1, 422);

        $user = $this->base()->findOrFail((int) (array_values($this->selected)[0] ?? 0));
        abort_if($user->is_company_owner && $user->id !== auth()->id() && ! auth()->user()->is_company_owner, 403);

        $plain = Str::password(12, symbols: false);

        DB::transaction(function () use ($user, $plain) {
            $user->update(['password' => $plain, 'must_change_password' => true]);
            AuditLogger::log('password_reset', 'users', $user->id, null, ['email' => $user->email]);
        });

        $this->resetInfo = ['name' => $user->name, 'email' => $user->email, 'password' => $plain];
        $this->selected = [];
    }

    public function dismissReset(): void
    {
        $this->resetInfo = null;
    }

    /** User n'a PAS de filtre automatique : on restreint toujours explicitement à l'entreprise. */
    private function base(): Builder
    {
        return User::where('company_id', auth()->user()->company_id);
    }

    private function query(): Builder
    {
        $search = trim($this->search);
        $status = $this->filters['status'] ?? '';

        return $this->base()
            ->with('employee:id,matricule,first_name,last_name')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', $search.'%')
                ->orWhereHas('employee', fn ($e) => $e->where('matricule', 'like', $search.'%'))))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderBy($this->orderColumn('name'), $this->orderDirection());
    }

    public function render()
    {
        return view('livewire.users.user-index', ['rows' => $this->query()->paginate($this->pageSize())]);
    }
}
