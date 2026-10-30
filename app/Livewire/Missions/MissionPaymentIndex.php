<?php

namespace App\Livewire\Missions;

use App\Enums\MissionStatus;
use App\Livewire\Concerns\AuthorizesResource;
use App\Models\MissionPayment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Poste de travail de la Finance : toutes les tranches de perdiem à payer, en retard ou payées. */
#[Layout('components.layouts.app')]
#[Title('Paiements des perdiems')]
class MissionPaymentIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = 'due';

    public function mount(): void
    {
        $this->authorizeAction('read', 'mission_payments');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    private function base()
    {
        return MissionPayment::query()->whereHas('mission', fn ($m) => $m
            ->where('company_id', auth()->user()->company_id)
            ->where('status', MissionStatus::Approved->value));
    }

    #[Computed]
    public function kpis(): array
    {
        return [
            'due' => (int) $this->base()->where('status', 'planned')->sum('planned_amount'),
            'overdue' => $this->base()->where('status', 'planned')->whereDate('planned_date', '<', today())->count(),
            'paidMonth' => (int) $this->base()->where('status', 'paid')->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('paid_amount'),
        ];
    }

    #[Computed]
    public function payments()
    {
        return $this->base()
            ->with(['mission:id,reference,destination_zone', 'participant.employee:id,first_name,last_name,matricule', 'participant.department:id,name'])
            ->when($this->status === 'due', fn ($q) => $q->where('status', 'planned'))
            ->when($this->status === 'overdue', fn ($q) => $q->where('status', 'planned')->whereDate('planned_date', '<', today()))
            ->when($this->status === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when(! in_array($this->status, ['due', 'overdue', 'paid'], true), fn ($q) => $q->whereIn('status', ['planned', 'paid']))
            ->orderByRaw('planned_date is null')->orderBy('planned_date')->orderBy('id')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.missions.mission-payment-index');
    }
}
