<?php

namespace App\Livewire\Dashboard;

use App\Services\CompanyDashboardData;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tableau de bord de l'entreprise. Les vues proposées dépendent des droits de l'utilisateur :
 * dashboard_hr = Direction et RH ; dashboard_payroll = Paie ; dashboard_department = Mon département ; toujours « Mon espace » si lié à un employé.
 */
#[Layout('components.layouts.app')]
#[Title('Tableau de bord')]
class CompanyDashboard extends Component
{
    #[Url(as: 'vue')]
    public string $view = '';

    public function mount(): void
    {
        $views = $this->views();

        if (! array_key_exists($this->view, $views)) {
            $this->view = (string) array_key_first($views);
        }
    }

    protected function views(): array
    {
        $u = auth()->user();
        $views = [];

        if ($u->canDo('read', 'dashboard_hr')) { $views['hr'] = 'Direction et RH'; }
        if ($u->canDo('read', 'dashboard_payroll')) { $views['payroll'] = 'Paie'; }
        if ($u->employee_id && $u->canDo('read', 'dashboard_department')) { $views['department'] = 'Mon département'; }
        if ($u->employee_id) { $views['self'] = 'Mon espace'; }

        return $views;
    }

    public function render()
    {
        $views = $this->views();
        $data = new CompanyDashboardData(auth()->user());

        $payload = match ($this->view) {
            'hr' => $data->hr(),
            'payroll' => $data->payroll(),
            'department' => $data->department(),
            'self' => $data->self(),
            default => null,
        };

        return view('livewire.dashboard.company-dashboard', ['views' => $views, 'd' => $payload]);
    }
}
