<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Payslip;
use App\Models\PayslipDelivery;
use App\Services\PayslipRenderer;
use Livewire\Component;

/** « Mes bulletins » : chaque employé voit uniquement ses propres bulletins clôturés. */
class MyPayslips extends Component
{
    use PayrollGuards;

    public function mount(): void
    {
        $this->allow('read', 'payslips');
    }

    public function render()
    {
        $employeeId = auth()->user()->employee_id;

        $payslips = $employeeId
            ? Payslip::where('employee_id', $employeeId)->where('status', 'final')->orderByDesc('period')->get()
            : collect();

        $viewed = $payslips->isEmpty()
            ? collect()
            : PayslipDelivery::whereIn('payslip_id', $payslips->pluck('id'))->where('channel', 'app')->whereNotNull('viewed_at')->pluck('viewed_at', 'payslip_id');

        return view('livewire.payroll.my-payslips', [
            'linked' => (bool) $employeeId,
            'years' => $payslips->groupBy(fn ($p) => substr($p->period, 0, 4)),
            'viewed' => $viewed,
            'labels' => $payslips->mapWithKeys(fn ($p) => [$p->id => PayslipRenderer::periodLabel($p->period)]),
        ])->layout($this->resolveLayout('company'), ['title' => 'Mes bulletins']);
    }
}
