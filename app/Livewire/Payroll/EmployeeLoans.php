<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalary;
use App\Models\LoanInstallment;
use App\Models\PayrollVariableSetting;
use App\Services\Payroll\LoanSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EmployeeLoans extends Component
{
    use PayrollGuards;

    public string $filterStatus = '';
    public string $search = '';
    public ?int $selectedId = null;

    public bool $showForm = false;
    public $employeeId = '';
    public string $type = 'loan';
    public string $label = '';
    public string $principal = '';
    public string $installment = '';
    public string $first_month = '';
    public string $interest_rate = '';
    public string $notes = '';
    public bool $acknowledge = false;

    public string $disbursedOn = '';
    public string $suspendFrom = '';
    public string $resumeMonth = '';
    public string $earlyAmount = '';

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'employee_loans');
        $this->disbursedOn = now()->toDateString();
        $this->suspendFrom = now()->format('Y-m');
        $this->resumeMonth = LoanSchedule::addMonths(now()->format('Y-m'), 1);
    }

    public function select(int $id): void
    {
        $this->selectedId = EmployeeLoan::findOrFail($id)->id;
        $this->earlyAmount = '';
    }

    // ───── Création ─────

    public function openForm(): void
    {
        $this->allow('create', 'employee_loans');
        $this->resetErrorBag();
        $this->employeeId = '';
        $this->type = 'loan';
        $this->label = '';
        $this->principal = '';
        $this->installment = '';
        $this->first_month = LoanSchedule::addMonths(now()->format('Y-m'), 1);
        $this->interest_rate = '';
        $this->notes = '';
        $this->acknowledge = false;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->allow('create', 'employee_loans');

        $this->interest_rate = str_replace(',', '.', trim($this->interest_rate));

        $this->validate([
            'employeeId' => 'required',
            'type' => 'required|in:loan,advance',
            'label' => 'nullable|string|max:120',
            'first_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'interest_rate' => 'nullable|numeric|between:0,100',
            'notes' => 'nullable|string|max:1000',
        ], ['employeeId.required' => 'Choisissez un employé.', 'first_month.regex' => 'Mois invalide.'], ['first_month' => 'mois de la première échéance', 'interest_rate' => "taux d'intérêt"]);

        $principal = $this->digits($this->principal);
        $installment = $this->digits($this->installment);

        if ($principal <= 0) {
            $this->addError('principal', 'Indiquez le montant du prêt.');

            return;
        }

        if ($installment <= 0) {
            $this->addError('installment', "Indiquez le montant de l'échéance mensuelle.");

            return;
        }

        $employee = Employee::findOrFail((int) $this->employeeId);
        $effort = $this->effortFor($employee->id, $installment);
        $threshold = PayrollVariableSetting::forCompany($this->companyId())->loan_effort_alert_percent;

        if ($effort !== null && $threshold !== null && $effort > (float) $threshold && ! $this->acknowledge) {
            $this->addError('acknowledge', "Le taux d'effort ($effort %) dépasse le seuil d'alerte ($threshold %). Cochez la case pour confirmer en connaissance de cause.");

            return;
        }

        $schedule = LoanSchedule::generate($principal, $installment, $this->first_month);

        $loan = DB::transaction(function () use ($employee, $principal, $installment, $schedule) {
            $loan = new EmployeeLoan([
                'employee_id' => $employee->id,
                'type' => $this->type,
                'label' => trim($this->label) ?: null,
                'principal' => $principal,
                'installment_amount' => $installment,
                'first_month' => $this->first_month,
                'interest_rate' => $this->interest_rate !== '' ? (float) $this->interest_rate : null,
                'status' => 'requested',
                'notes' => trim($this->notes) ?: null,
                'created_by' => auth()->id(),
            ]);
            $loan->company_id = $this->companyId();
            $loan->save();

            foreach ($schedule as $row) {
                LoanInstallment::create(['employee_loan_id' => $loan->id, 'due_month' => $row['month'], 'amount' => $row['amount'], 'status' => 'scheduled']);
            }

            return $loan;
        });

        $this->audit('create', 'employee_loans', $loan->id, null, [
            'employee_id' => $employee->id, 'type' => $this->type, 'principal' => $principal, 'installment' => $installment,
            'first_month' => $this->first_month, 'installments' => count($schedule),
        ]);

        $this->showForm = false;
        $this->selectedId = $loan->id;
        $this->flash = 'Demande enregistrée : ' . count($schedule) . ' échéance(s), à approuver avant le décaissement.';
    }

    // ───── Cycle de vie ─────

    public function approve(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'requested') {
            return;
        }

        $loan->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->audit('update', 'employee_loans', $loan->id, ['status' => 'requested'], ['status' => 'approved']);
        $this->flash = 'Demande approuvée. Enregistrez le décaissement pour lancer les prélèvements.';
    }

    public function cancel(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if (! in_array($loan->status, ['requested', 'approved'], true)) {
            return;
        }

        $loan->update(['status' => 'cancelled']);
        $this->audit('update', 'employee_loans', $loan->id, ['status' => $loan->getOriginal('status')], ['status' => 'cancelled']);
        $this->flash = 'Demande annulée.';
    }

    public function disburse(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'approved') {
            return;
        }

        $this->validate(['disbursedOn' => 'required|date'], [], ['disbursedOn' => 'date de décaissement']);

        $loan->update(['status' => 'active', 'disbursed_on' => Carbon::parse($this->disbursedOn)->toDateString()]);
        $this->audit('update', 'employee_loans', $loan->id, ['status' => 'approved'], ['status' => 'active', 'disbursed_on' => $loan->disbursed_on->toDateString()]);
        $this->flash = 'Décaissement enregistré : les échéances seront prélevées aux mois prévus.';
    }

    public function suspend(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'active') {
            return;
        }

        $this->validate(['suspendFrom' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']], ['suspendFrom.regex' => 'Mois invalide.'], ['suspendFrom' => 'mois de début de suspension']);

        $count = $loan->installments()->where('status', 'scheduled')->where('due_month', '>=', $this->suspendFrom)->update(['status' => 'suspended']);

        if ($count === 0) {
            $this->flash = 'Aucune échéance à suspendre à partir de ce mois.';

            return;
        }

        $loan->update(['status' => 'suspended']);
        $this->audit('update', 'employee_loans', $loan->id, ['status' => 'active'], ['status' => 'suspended', 'from' => $this->suspendFrom, 'installments' => $count]);
        $this->flash = $count . ' échéance(s) suspendue(s) à partir de ' . $this->suspendFrom . '.';
    }

    public function resume(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'suspended') {
            return;
        }

        $this->validate(['resumeMonth' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']], ['resumeMonth.regex' => 'Mois invalide.'], ['resumeMonth' => 'mois de reprise']);

        $suspended = $loan->installments()->where('status', 'suspended')->orderBy('due_month')->get();
        $months = LoanSchedule::reschedule($suspended->count(), $this->resumeMonth);

        DB::transaction(function () use ($suspended, $months) {
            foreach ($suspended as $i => $row) {
                $row->update(['due_month' => $months[$i], 'status' => 'scheduled']);
            }
        });

        $loan->update(['status' => 'active']);
        $this->audit('update', 'employee_loans', $loan->id, ['status' => 'suspended'], ['status' => 'active', 'resume_month' => $this->resumeMonth]);
        $this->flash = 'Prêt repris : ' . $suspended->count() . ' échéance(s) replanifiée(s) à partir de ' . $this->resumeMonth . '.';
    }

    public function earlyRepay(int $id): void
    {
        $this->allow('update', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if (! in_array($loan->status, ['active', 'suspended'], true)) {
            return;
        }

        $amount = $this->digits($this->earlyAmount);

        if ($amount <= 0) {
            $this->addError('earlyAmount', 'Indiquez le montant remboursé.');

            return;
        }

        $rows = $loan->installments()->whereIn('status', ['scheduled', 'suspended'])->orderBy('due_month')->get();
        $result = LoanSchedule::applyEarlyRepayment($rows->pluck('amount')->map(fn ($a) => (int) $a)->all(), $amount);

        if ($result['surplus'] > 0) {
            $this->addError('earlyAmount', 'Ce montant dépasse le solde restant dû (' . number_format($rows->sum('amount'), 0, ',', ' ') . ').');

            return;
        }

        DB::transaction(function () use ($rows, $result) {
            foreach ($rows as $i => $row) {
                $new = $result['amounts'][$i];

                if ($new !== (int) $row->amount) {
                    $row->update($new === 0 ? ['amount' => 0, 'status' => 'prepaid'] : ['amount' => $new]);
                }
            }
        });

        $loan->refresh()->syncStatus();
        $this->audit('update', 'employee_loans', $loan->id, null, ['early_repayment' => $amount]);
        $this->earlyAmount = '';
        $this->flash = 'Remboursement anticipé de ' . number_format($amount, 0, ',', ' ') . ' enregistré : il s\'impute sur les dernières échéances.';
    }

    public function deleteLoan(int $id): void
    {
        $this->allow('delete', 'employee_loans');
        $loan = EmployeeLoan::findOrFail($id);

        if (! in_array($loan->status, ['requested', 'cancelled'], true)) {
            $this->flash = 'Seule une demande non décaissée (demandée ou annulée) peut être supprimée.';

            return;
        }

        $this->audit('delete', 'employee_loans', $loan->id, ['employee_id' => $loan->employee_id, 'principal' => $loan->principal], null);
        $loan->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
        }

        $this->flash = 'Demande supprimée.';
    }

    // ───── Aides ─────

    /** Salaire mensuel de référence (base + sursalaire en vigueur aujourd'hui). */
    protected function monthlyPay(int $employeeId): float
    {
        $salary = EmployeeSalary::where('employee_id', $employeeId)->inForceOn(now()->toDateString())->orderByDesc('effective_from')->first();

        return $salary ? (float) $salary->base_amount + (float) $salary->supplement_amount : 0.0;
    }

    protected function effortFor(int $employeeId, int $installment): ?float
    {
        return LoanSchedule::effortPercent($installment, $this->monthlyPay($employeeId));
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->search));
        $employees = Employee::orderBy('matricule')->get();
        $names = $employees->mapWithKeys(fn ($e) => [$e->id => $this->employeeName($e)]);

        $remaining = LoanInstallment::selectRaw('employee_loan_id, SUM(amount) as total')->whereIn('status', ['scheduled', 'suspended'])
            ->groupBy('employee_loan_id')->pluck('total', 'employee_loan_id');

        $loans = EmployeeLoan::when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('id')->get()
            ->filter(fn ($l) => $term === '' || str_contains(mb_strtolower($names[$l->employee_id] ?? ''), $term))
            ->values();

        $selected = $this->selectedId ? EmployeeLoan::with('installments')->find($this->selectedId) : null;

        $threshold = PayrollVariableSetting::forCompany($this->companyId())->loan_effort_alert_percent;

        $formPrincipal = $this->digits($this->principal);
        $formInstallment = $this->digits($this->installment);
        $formEffort = $this->showForm && $this->employeeId !== '' && $formInstallment > 0 ? $this->effortFor((int) $this->employeeId, $formInstallment) : null;
        $formCount = ($formPrincipal > 0 && $formInstallment > 0) ? count(LoanSchedule::generate($formPrincipal, $formInstallment, $this->first_month !== '' ? $this->first_month : now()->format('Y-m'))) : 0;

        return view('livewire.payroll.employee-loans', [
            'loans' => $loans->take(300),
            'names' => $names,
            'remaining' => $remaining,
            'selected' => $selected,
            'selectedName' => $selected ? ($names[$selected->employee_id] ?? '') : '',
            'selectedEffort' => $selected ? $this->effortFor($selected->employee_id, (int) $selected->installment_amount) : null,
            'employees' => $employees->map(fn ($e) => ['id' => $e->id, 'label' => ($e->matricule ? $e->matricule . ' · ' : '') . $names[$e->id]]),
            'threshold' => $threshold,
            'formEffort' => $formEffort,
            'formCount' => $formCount,
            'types' => EmployeeLoan::TYPES,
            'statuses' => EmployeeLoan::STATUSES,
            'instStatuses' => LoanInstallment::STATUSES,
            'canCreate' => $this->allowed('create', 'employee_loans'),
            'canEdit' => $this->allowed('update', 'employee_loans'),
            'canDelete' => $this->allowed('delete', 'employee_loans'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Prêts et avances']);
    }
}
