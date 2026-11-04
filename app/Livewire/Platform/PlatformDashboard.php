<?php

namespace App\Livewire\Platform;

use App\Services\PlatformStats;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.platform')]
#[Title('Tableau de bord')]
class PlatformDashboard extends Component
{
    public string $pivotBy = 'sector';
    public string $pivotMetric = 'companies';

    public function mount(): void
    {
        abort_unless(auth()->user()->platformCan('dashboard.view'), 403);
    }

    public function refreshStats(): void
    {
        PlatformStats::forget();
        $this->dispatch('notify', type: 'success', message: 'Indicateurs actualisés.');
    }

    public function render()
    {
        $stats = PlatformStats::get();

        return view('livewire.platform.platform-dashboard', [
            's' => $stats,
            'pivot' => PlatformStats::pivot($stats['rows'], in_array($this->pivotBy, ['sector', 'country', 'period'], true) ? $this->pivotBy : 'sector', in_array($this->pivotMetric, ['companies', 'employees', 'mrr'], true) ? $this->pivotMetric : 'companies'),
        ]);
    }
}
