<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use Livewire\Component;

/** Page « Tableau de bord paie » (menu Paie) : héberge le panneau, qui porte tout le contenu et ses droits. */
class PayrollDashboard extends Component
{
    use PayrollGuards;

    public function mount(): void
    {
        $this->allow('read', 'dashboard_payroll');
    }

    public function render()
    {
        return view('livewire.payroll.payroll-dashboard')->layout($this->resolveLayout('company'), ['title' => 'Tableau de bord paie']);
    }
}
