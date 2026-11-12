<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayrollInputBuilder;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Simulation de paie d'un employé pour un mois : calcule et explique, n'enregistre rien. */
class PayrollSimulation extends Component
{
    use PayrollGuards;

    #[Url]
    public $employeeId = '';
    #[Url]
    public string $period = '';
    /** Numéro d'un bulletin enregistré : on affiche alors le bulletin figé, sans le recalculer. */
    #[Url]
    public $payslip = '';
    public string $referenceNet = '';
    public string $infirmAdults = '0';

    public function mount(): void
    {
        $this->allow('read', 'payroll_simulation');
        if ($this->period === '') {
            $this->period = now()->format('Y-m');
        }
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
        $frozen = null;

        if ($this->payslip !== '' && $this->payslip !== null) {
            $saved = Payslip::find((int) $this->payslip);

            if (! $saved) {
                $error = 'Bulletin introuvable.';
            } else {
                $snapshot = $saved->snapshot;
                $sim = $snapshot['result'];
                $meta = $snapshot['meta'];
                $meta['notes'] = [];
                $its = PayrollCalculator::explainIts($snapshot['rules'], $sim['totals']['its_base'], $snapshot['family']);
                $reference = $this->digits($this->referenceNet);
                $gap = $reference > 0 ? $sim['totals']['net_to_pay'] - $reference : null;
                $frozen = ($saved->status === 'final' ? 'Bulletin clôturé' : 'Bulletin provisoire') . ' de ' . substr($saved->period, 5, 2) . '/' . substr($saved->period, 0, 4)
                    . ' : les chiffres ci-dessous sont ceux enregistrés au calcul du ' . ($snapshot['calculated_at'] ? \Carbon\Carbon::parse($snapshot['calculated_at'])->format('d/m/Y à H:i') : '—')
                    . ', ils ne changent pas si les données évoluent ensuite.';
            }
        } elseif ($this->employeeId !== '' && $this->employeeId !== null) {
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
            'frozen' => $frozen,
        ])->layout($this->resolveLayout('company'), ['title' => 'Simulation de paie']);
    }
}
