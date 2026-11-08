<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayrollInputBuilder;
use Livewire\Component;

/** Simulation de paie d'un employé pour un mois : calcule et explique, n'enregistre rien. */
class PayrollSimulation extends Component
{
    use PayrollGuards;

    public $employeeId = '';
    public string $period = '';
    public string $referenceNet = '';
    public string $infirmAdults = '0';

    public function mount(): void
    {
        $this->allow('read', 'payroll_simulation');
        $this->period = now()->format('Y-m');
    }

    public function updatedPeriod(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->period)) {
            $this->period = now()->format('Y-m');
        }
    }

    public function render()
    {
        $employees = Employee::orderBy('matricule')->get();

        $sim = null;
        $meta = null;
        $its = null;
        $error = null;
        $gap = null;

        if ($this->employeeId !== '' && $this->employeeId !== null) {
            try {
                $built = PayrollInputBuilder::build((int) $this->employeeId, $this->period, ['infirm_adults' => min(10, $this->digits($this->infirmAdults))]);
                $meta = $built['meta'];
                $sim = PayrollCalculator::compute($built['input']);
                $its = PayrollCalculator::explainIts($built['input']['rules'], $sim['totals']['its_base'], $built['input']['family']);

                $reference = $this->digits($this->referenceNet);
                $gap = $reference > 0 ? $sim['totals']['net_to_pay'] - $reference : null;
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('livewire.payroll.payroll-simulation', [
            'employees' => $employees->map(fn ($e) => ['id' => $e->id, 'label' => ($e->matricule ? $e->matricule . ' · ' : '') . $this->employeeName($e)]),
            'sim' => $sim,
            'meta' => $meta,
            'its' => $its,
            'error' => $error,
            'gap' => $gap,
        ])->layout($this->resolveLayout('company'), ['title' => 'Simulation de paie']);
    }
}
