<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\LoanInstallment;
use App\Models\OvertimeRate;
use App\Models\PayItem;
use App\Models\PayrollVariable;
use App\Services\Payroll\LoanSchedule;
use App\Services\Payroll\PayrollDays;
use App\Services\Payroll\PayrollPeriodGuard;
use App\Services\Payroll\PayVariables;
use App\Services\Payroll\PayVariableSync;
use Livewire\Component;

class MonthlyVariables extends Component
{
    use PayrollGuards;

    public string $period = '';
    public string $search = '';
    public string $statusFilter = '';

    public bool $showForm = false;
    public $employeeId = '';
    public string $kind = 'overtime_hours';
    public $payItemId = '';
    public string $quantity = '';
    public $ratePercent = '';
    public string $amount = '';
    public string $daysMode = 'working';
    public string $note = '';

    public string $flash = '';
    /** @var array<int, string> */
    public array $syncNotes = [];

    public function mount(): void
    {
        $this->allow('read', 'payroll_variables');
        $this->period = now()->format('Y-m');

        // Les propositions du mois courant se rafraîchissent d'elles-mêmes à l'ouverture : plus besoin de cliquer.
        if ($this->allowed('update', 'payroll_variables') && ! PayrollPeriodGuard::isClosed($this->period)) {
            try {
                $result = PayVariableSync::run($this->period, $this->companyId());
                $this->syncNotes = $result['notes'];

                if ($result['created'] + $result['updated'] + $result['removed'] > 0) {
                    $this->flash = $result['created'] . ' proposition(s) ajoutée(s) automatiquement, ' . $result['updated'] . ' mise(s) à jour, ' . $result['removed'] . ' retirée(s).';
                }
            } catch (\Throwable $e) {
                $this->syncNotes = ['Les propositions automatiques n\'ont pas pu être mises à jour : ' . $e->getMessage()];
            }
        }
    }

    public function updatedPeriod(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->period)) {
            $this->period = now()->format('Y-m');
        }

        $this->syncNotes = [];
    }

    public function previousMonth(): void
    {
        $this->period = LoanSchedule::addMonths($this->period, -1);
        $this->syncNotes = [];
    }

    public function nextMonth(): void
    {
        $this->period = LoanSchedule::addMonths($this->period, 1);
        $this->syncNotes = [];
    }

    // ───── Propositions automatiques ─────

    public function refreshProposals(): void
    {
        $this->allow('update', 'payroll_variables');

        $result = PayVariableSync::run($this->period, $this->companyId());

        $this->syncNotes = $result['notes'];
        $this->flash = $result['created'] . ' proposition(s) ajoutée(s), ' . $result['updated'] . ' mise(s) à jour, ' . $result['removed'] . ' retirée(s) (source disparue).';
        $this->audit('create', 'payroll_variables', null, null, ['period' => $this->period] + array_diff_key($result, ['notes' => 1]));
    }

    public function confirm(int $id): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);
        $v->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        $this->audit('update', 'payroll_variables', $v->id, ['status' => $v->getOriginal('status')], ['status' => 'confirmed']);
    }

    public function ignore(int $id): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);
        $v->update(['status' => 'ignored', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        $this->audit('update', 'payroll_variables', $v->id, ['status' => $v->getOriginal('status')], ['status' => 'ignored']);
    }

    public function reopen(int $id): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);

        if (! $v->isAuto()) {
            return;
        }

        $v->update(['status' => 'proposed', 'confirmed_by' => null, 'confirmed_at' => null]);
        $this->audit('update', 'payroll_variables', $v->id, ['status' => $v->getOriginal('status')], ['status' => 'proposed']);
    }

    public function confirmAllProposed(): void
    {
        $this->allow('update', 'payroll_variables');

        $count = PayrollVariable::where('period', $this->period)->where('status', 'proposed')
            ->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);

        $this->audit('update', 'payroll_variables', null, null, ['period' => $this->period, 'confirmed_all' => $count]);
        $this->flash = $count . ' variable(s) confirmée(s).';
    }

    // ───── Modifications en ligne ─────

    public function saveQuantity(int $id, $value): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);
        $text = str_replace(',', '.', trim((string) $value));

        if (! is_numeric($text) || (float) $text <= 0 || (float) $text > 9999) {
            $this->flash = 'La quantité doit être un nombre supérieur à 0.';

            return;
        }

        $old = $v->quantity;
        $v->update(['quantity' => round((float) $text, 2), 'is_edited' => true]);
        $this->audit('update', 'payroll_variables', $v->id, ['quantity' => $old], ['quantity' => $v->quantity]);
    }

    public function saveAmount(int $id, $value): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);
        $amount = trim((string) $value) === '' ? null : $this->digits($value);

        if ($amount === 0) {
            $amount = null;
        }

        $old = $v->amount_override;
        $v->update(['amount_override' => $amount, 'is_edited' => true]);
        $this->audit('update', 'payroll_variables', $v->id, ['amount_override' => $old], ['amount_override' => $amount]);
    }

    public function saveRate(int $id, $value): void
    {
        $this->allow('update', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);

        if (! in_array($v->kind, ['overtime_hours', 'worked_days'], true)) {
            return;
        }

        $rate = $value === '' || $value === null ? null : (float) $value;

        if ($rate !== null && ! OvertimeRate::where('is_active', true)->where('percent', $rate)->exists()) {
            $this->flash = 'Cette majoration n\'existe pas dans vos réglages.';

            return;
        }

        $old = $v->rate_percent;
        $v->update(['rate_percent' => $rate, 'is_edited' => true]);
        $this->audit('update', 'payroll_variables', $v->id, ['rate_percent' => $old], ['rate_percent' => $rate]);
    }

    // ───── Saisie manuelle ─────

    public function openForm(): void
    {
        $this->allow('update', 'payroll_variables');
        $this->resetErrorBag();
        $this->employeeId = '';
        $this->kind = 'overtime_hours';
        $this->payItemId = '';
        $this->quantity = '';
        $this->ratePercent = '';
        $this->amount = '';
        $this->daysMode = 'working';
        $this->note = '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function addVariable(): void
    {
        $this->allow('update', 'payroll_variables');

        $this->validate([
            'employeeId' => 'required',
            'kind' => 'required|in:' . implode(',', array_keys(PayrollVariable::KINDS)),
            'note' => 'nullable|string|max:500',
        ], ['employeeId.required' => 'Choisissez un employé.'], ['kind' => 'nature']);

        $employee = Employee::findOrFail((int) $this->employeeId);
        $data = ['employee_id' => $employee->id, 'period' => $this->period, 'kind' => $this->kind, 'note' => trim($this->note) ?: null];

        if (in_array($this->kind, ['bonus', 'deduction'], true)) {
            if ($this->payItemId === '' || $this->payItemId === null) {
                $this->addError('payItemId', 'Choisissez la rubrique.');

                return;
            }

            $item = PayItem::where('is_system', false)->where('is_active', true)->findOrFail((int) $this->payItemId);
            $amount = $this->digits($this->amount);

            if ($amount <= 0) {
                $this->addError('amount', 'Indiquez le montant.');

                return;
            }

            $data += ['pay_item_id' => $item->id, 'amount_override' => $amount];
        } else {
            $text = str_replace(',', '.', trim($this->quantity));

            if (! is_numeric($text) || (float) $text <= 0 || (float) $text > 9999) {
                $this->addError('quantity', 'Indiquez une quantité supérieure à 0.');

                return;
            }

            $data += [
                'quantity' => round((float) $text, 2),
                'unit' => $this->kind === 'overtime_hours' ? 'hours' : 'days',
                'days_mode' => $this->kind === 'suspension' ? $this->daysMode : null,
            ];

            if (in_array($this->kind, ['overtime_hours', 'worked_days'], true) && $this->ratePercent !== '' && $this->ratePercent !== null) {
                $rate = (float) $this->ratePercent;

                if (! OvertimeRate::where('is_active', true)->where('percent', $rate)->exists()) {
                    $this->addError('ratePercent', 'Cette majoration n\'existe pas dans vos réglages.');

                    return;
                }

                $data['rate_percent'] = $rate;
            }

            if ($this->amount !== '' && $this->digits($this->amount) > 0) {
                $data['amount_override'] = $this->digits($this->amount);
            }
        }

        PayVariableSync::ensureSystemItems($this->companyId());

        $row = new PayrollVariable($data + ['status' => 'confirmed', 'created_by' => auth()->id(), 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        $row->company_id = $this->companyId();
        $row->save();

        $this->audit('create', 'payroll_variables', $row->id, null, $data);
        $this->showForm = false;
        $this->flash = 'Variable ajoutée et confirmée pour ' . $this->employeeName($employee) . '.';
    }

    public function deleteVariable(int $id): void
    {
        $this->allow('delete', 'payroll_variables');
        $v = PayrollVariable::findOrFail($id);

        if ($v->isAuto()) {
            $this->flash = 'Une ligne venue d\'une source (absence, mise à pied, jours travaillés) ne se supprime pas : marquez-la « ignorée ».';

            return;
        }

        $this->audit('delete', 'payroll_variables', $v->id, ['kind' => $v->kind, 'employee_id' => $v->employee_id], null);
        $v->delete();
        $this->flash = 'Variable supprimée.';
    }

    public function render()
    {
        $closed = PayrollPeriodGuard::isClosed($this->period);
        $term = mb_strtolower(trim($this->search));

        $employees = Employee::orderBy('matricule')->get();
        $names = $employees->mapWithKeys(fn ($e) => [$e->id => $this->employeeName($e)]);

        $variables = PayrollVariable::with('item')->where('period', $this->period)
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('employee_id')->orderBy('id')->get()
            ->filter(fn ($v) => $term === '' || str_contains(mb_strtolower($names[$v->employee_id] ?? ''), $term))
            ->values();

        $ctx = PayVariables::context($this->companyId(), $variables->pluck('employee_id')->unique()->all(), $this->period);

        $groups = [];

        foreach ($variables->groupBy('employee_id') as $employeeId => $rows) {
            $groups[] = [
                'name' => $names[$employeeId] ?? ('#' . $employeeId),
                'noSalary' => ! isset($ctx['pay'][(int) $employeeId]),
                'rows' => $rows->map(fn ($v) => [
                    'v' => $v,
                    'label' => $v->item?->label ?? (PayrollVariable::KINDS[$v->kind] ?? $v->kind),
                    'amount' => PayVariables::amountFor($v, $ctx),
                    'effect' => PayVariables::effectOf($v),
                ])->all(),
            ];
        }

        $installments = LoanInstallment::with('loan')->where('due_month', $this->period)->whereIn('status', ['scheduled', 'suspended'])
            ->whereHas('loan', fn ($q) => $q->whereIn('status', ['active', 'suspended']))->get()
            ->map(fn ($i) => ['i' => $i, 'name' => $names[$i->loan->employee_id] ?? ('#' . $i->loan->employee_id)])
            ->sortBy('name')->values();

        return view('livewire.payroll.monthly-variables', [
            'groups' => $groups,
            'installments' => $installments,
            'proposedCount' => PayrollVariable::where('period', $this->period)->where('status', 'proposed')->count(),
            'employees' => $employees->map(fn ($e) => ['id' => $e->id, 'label' => ($e->matricule ? $e->matricule . ' · ' : '') . $names[$e->id]]),
            'rates' => OvertimeRate::where('is_active', true)->orderBy('sort')->orderBy('percent')->get(),
            'bonusItems' => PayItem::where('is_system', false)->where('is_active', true)->whereIn('type', ['gain', 'benefit_in_kind'])->orderBy('code')->get(),
            'deductionItems' => PayItem::where('is_system', false)->where('is_active', true)->whereIn('type', ['gross_deduction', 'deduction_internal', 'deduction_individual'])->orderBy('code')->get(),
            'kinds' => array_diff_key(PayrollVariable::KINDS, ['assignment' => 1]),
            'monthLabel' => $this->monthLabel(),
            'closed' => $closed,
            'canEdit' => $this->allowed('update', 'payroll_variables') && ! $closed,
            'canDelete' => $this->allowed('delete', 'payroll_variables') && ! $closed,
        ])->layout($this->resolveLayout('company'), ['title' => 'Variables du mois']);
    }

    protected function monthLabel(): string
    {
        [$start] = PayrollDays::monthBounds($this->period);
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return $months[(int) substr($start, 5, 2) - 1] . ' ' . substr($start, 0, 4);
    }
}
