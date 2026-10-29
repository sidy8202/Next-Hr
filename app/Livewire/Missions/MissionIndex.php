<?php

namespace App\Livewire\Missions;

use App\Enums\MissionStatus;
use App\Livewire\Concerns\AuthorizesResource;
use App\Services\MissionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Ordres de mission')]
class MissionIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'mission_orders');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function missions()
    {
        return app(MissionService::class)->visibleQuery(auth()->user())
            ->with('department:id,name', 'requester:id,name')->withCount('participants')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()->paginate(15);
    }

    public function render()
    {
        return view('livewire.missions.mission-index', ['statuses' => MissionStatus::cases()]);
    }
}
