<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Employee;
use App\Models\EmployeePayItem;
use App\Models\PayItem;
use App\Services\Payroll\PayComposition;
use Illuminate\Support\Carbon;
use Livewire\Component;

class EmployeePayItems extends Component
{
    use PayrollGuards;

    public string $search = '';
    public string $filterStatus = 'current';

    public bool $showForm = false;
    public $employeeId = '';
    public $itemId = '';
    public string $amount = '';
    public string $rate = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $reason = '';

    public ?int $endingId = null;
    public string $endDate = '';

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'employee_pay_items');
    }

    public function openForm(): void
    {
        $this->allow('update', 'employee_pay_items');
        $this->resetErrorBag();
        $this->employeeId = '';
        $this->itemId = '';
        $this->amount = '';
        $this->rate = '';
        $this->start_date = now()->toDateString();
        $this->end_date = '';
        $this->reason = '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->allow('update', 'employee_pay_items');

        $this->validate([
            'employeeId' => 'required',
            'itemId' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ], [
            'employeeId.required' => 'Choisissez un employé.',
            'itemId.required' => 'Choisissez une rubrique.',
            'end_date.after_or_equal' => 'La fin ne peut pas précéder le début.',
        ], ['start_date' => 'date de début', 'end_date' => 'date de fin']);

        $employee = Employee::findOrFail((int) $this->employeeId);
        $item = PayItem::where('is_system', false)->where('is_active', true)->findOrFail((int) $this->itemId);

        $amount = null;
        $rate = null;

        if (PayItem::usesAmount($item->calc_type) && trim($this->amount) !== '') {
            $amount = $this->digits($this->amount);
        }

        if (PayItem::usesRate($item->calc_type) && trim($this->rate) !== '') {
            $text = str_replace(',', '.', trim($this->rate));

            if (! is_numeric($text) || (float) $text < 0 || (float) $text > 100) {
                $this->addError('rate', 'Le taux doit être un nombre entre 0 et 100.');

                return;
            }

            $rate = (float) $text;
        }

        $needsValue = PayItem::usesAmount($item->calc_type) || PayItem::usesRate($item->calc_type);

        if ($needsValue && $amount === null && $rate === null && $item->default_amount === null && $item->default_rate === null) {
            $this->addError('amount', "Indiquez une valeur : cette rubrique n'a pas de valeur par défaut.");

            return;
        }

        $start = Carbon::parse($this->start_date)->toDateString();
        $end = $this->end_date !== '' ? Carbon::parse($this->end_date)->toDateString() : null;

        $conflict = EmployeePayItem::where('employee_id', $employee->id)->where('pay_item_id', $item->id)->get()
            ->contains(fn ($r) => PayComposition::overlaps($r->start_date->toDateString(), $r->end_date?->toDateString(), $start, $end));

        if ($conflict) {
            $this->addError('itemId', 'Cet employé a déjà cette rubrique sur une période qui chevauche celle-ci : terminez l\'ancienne attribution ou choisissez d\'autres dates.');

            return;
        }

        $row = new EmployeePayItem([
            'employee_id' => $employee->id,
            'pay_item_id' => $item->id,
            'amount' => $amount,
            'rate' => $rate,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => trim($this->reason) ?: null,
            'created_by' => auth()->id(),
        ]);
        $row->company_id = $this->companyId();
        $row->save();

        $this->audit('create', 'employee_pay_items', $row->id, null, [
            'employee_id' => $employee->id, 'item' => $item->code, 'amount' => $amount, 'rate' => $rate, 'start' => $start, 'end' => $end,
        ]);

        $this->showForm = false;
        $this->flash = 'Prime « ' . $item->label . ' » attribuée à ' . $this->employeeName($employee) . ' à partir du ' . Carbon::parse($start)->format('d/m/Y') . '.';
    }

    public function askEnd(int $id): void
    {
        $this->allow('update', 'employee_pay_items');
        $this->endingId = EmployeePayItem::findOrFail($id)->id;
        $this->endDate = now()->toDateString();
        $this->resetErrorBag();
    }

    public function cancelEnd(): void
    {
        $this->endingId = null;
        $this->resetErrorBag();
    }

    public function confirmEnd(): void
    {
        $this->allow('update', 'employee_pay_items');
        $row = EmployeePayItem::with('item')->findOrFail((int) $this->endingId);

        $this->validate(['endDate' => 'required|date|after_or_equal:' . $row->start_date->toDateString()], [
            'endDate.after_or_equal' => 'La fin ne peut pas précéder le début (' . $row->start_date->format('d/m/Y') . ').',
        ], ['endDate' => 'date de fin']);

        $old = $row->end_date?->toDateString();
        $row->update(['end_date' => Carbon::parse($this->endDate)->toDateString()]);
        $this->audit('update', 'employee_pay_items', $row->id, ['end' => $old], ['end' => $row->end_date->toDateString()]);
        $this->flash = 'Prime « ' . $row->item->label . ' » terminée le ' . $row->end_date->format('d/m/Y') . '.';
        $this->endingId = null;
    }

    public function deleteRow(int $id): void
    {
        $this->allow('delete', 'employee_pay_items');
        $row = EmployeePayItem::with('item')->findOrFail($id);
        $this->audit('delete', 'employee_pay_items', $row->id, ['item' => $row->item->code, 'employee_id' => $row->employee_id], null);
        $row->delete();
        $this->flash = 'Attribution supprimée.';
    }

    public function render()
    {
        $today = now()->toDateString();
        $term = mb_strtolower(trim($this->search));

        $employees = Employee::orderBy('matricule')->get();
        $names = $employees->mapWithKeys(fn ($e) => [$e->id => $this->employeeName($e)]);

        $rows = EmployeePayItem::with('item')->orderByDesc('start_date')->get()
            ->map(function ($r) use ($today, $names) {
                $status = $r->start_date->toDateString() > $today ? 'future' : (($r->end_date && $r->end_date->toDateString() < $today) ? 'ended' : 'current');

                return ['row' => $r, 'name' => $names[$r->employee_id] ?? ('#' . $r->employee_id), 'status' => $status];
            })
            ->filter(fn ($x) => $this->filterStatus === '' || $x['status'] === $this->filterStatus)
            ->filter(fn ($x) => $term === '' || str_contains(mb_strtolower($x['name']), $term) || str_contains(mb_strtolower($x['row']->item->label), $term))
            ->values();

        return view('livewire.payroll.employee-pay-items', [
            'rows' => $rows->take(300),
            'total' => $rows->count(),
            'employees' => $employees->map(fn ($e) => ['id' => $e->id, 'label' => ($e->matricule ? $e->matricule . ' · ' : '') . $names[$e->id]]),
            'items' => PayItem::where('is_system', false)->where('is_active', true)->orderBy('code')->get(),
            'canEdit' => $this->allowed('update', 'employee_pay_items'),
            'canDelete' => $this->allowed('delete', 'employee_pay_items'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Primes attribuées']);
    }
}
