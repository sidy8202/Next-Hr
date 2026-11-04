<?php

namespace App\Livewire\Platform;

use App\Models\PlatformAuditLog;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.platform')]
#[Title('Journal d’audit')]
class AuditIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'qui')] public string $user = '';
    #[Url(as: 'du')] public string $from = '';
    #[Url(as: 'au')] public string $to = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('audit.view'), 403);
    }

    public function updated($name): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs()
    {
        return PlatformAuditLog::with('user:id,name')
            ->when($this->search !== '', fn ($q) => $q->where('summary', 'like', "%{$this->search}%"))
            ->when($this->user !== '', fn ($q) => $q->where('user_id', $this->user))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest('id')->paginate(25);
    }

    public function render()
    {
        return view('livewire.platform.audit-index', ['staff' => User::where('is_platform_admin', true)->orderBy('name')->get(['id', 'name'])]);
    }
}
