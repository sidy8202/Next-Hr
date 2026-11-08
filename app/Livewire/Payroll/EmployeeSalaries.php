<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EmployeeSalaries extends Component
{
    use PayrollGuards;

    public string $search = '';
    public bool $showForm = false;
    public ?int $employeeId = null;
    public string $employeeLabel = '';

    public string $effective_from = '';
    public $salary_grade_id = '';
    public $salary_step_id = '';
    public string $mode = 'gross';
    public string $base_amount = '';
    public string $supplement_amount = '0';
    public string $target_net = '';
    public string $notes = '';

    public ?int $historyId = null;
    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'employee_salaries');
    }

    // ───── Formulaire ─────

    public function openForm(int $employeeId): void
    {
        $this->allow('update', 'employee_salaries');
        $this->resetErrorBag();

        $employee = Employee::findOrFail($employeeId);
        $current = EmployeeSalary::where('employee_id', $employee->id)->inForceOn(now()->toDateString())->orderByDesc('effective_from')->first();

        $this->employeeId = $employee->id;
        $this->employeeLabel = $this->employeeName($employee);
        $this->effective_from = now()->toDateString();
        $this->salary_grade_id = $current?->salary_grade_id ? (string) $current->salary_grade_id : '';
        $this->salary_step_id = $current?->salary_step_id ? (string) $current->salary_step_id : '';
        $this->mode = $current?->mode ?? 'gross';
        $this->base_amount = $current ? (string) $current->base_amount : '';
        $this->supplement_amount = $current ? (string) $current->supplement_amount : '0';
        $this->target_net = $current?->target_net ? (string) $current->target_net : '';
        $this->notes = '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function updatedSalaryGradeId($value): void
    {
        if ($this->salary_step_id !== '' && ! SalaryStep::whereKey((int) $this->salary_step_id)->where('salary_grade_id', (int) $value)->exists()) {
            $this->salary_step_id = '';
        }
    }

    public function updatedSalaryStepId($value): void
    {
        if ($value === '' || $value === null) {
            return;
        }

        $step = SalaryStep::whereHas('grade.grid')->find((int) $value);

        if ($step) {
            $this->salary_grade_id = (string) $step->salary_grade_id;
            $this->base_amount = (string) $step->amount;
        }
    }

    public function save(): void
    {
        $this->allow('update', 'employee_salaries');

        $this->validate([
            'employeeId' => 'required|integer',
            'effective_from' => 'required|date',
            'mode' => 'required|in:gross,net_target',
            'notes' => 'nullable|string|max:1000',
        ], [], ['effective_from' => "date d'effet", 'mode' => 'mode de salaire']);

        $base = $this->digits($this->base_amount);
        $supplement = $this->digits($this->supplement_amount);
        $targetNet = $this->digits($this->target_net);

        if ($this->mode === 'gross' && $base <= 0) {
            $this->addError('base_amount', 'Indiquez le salaire de base.');

            return;
        }

        if ($this->mode === 'net_target' && $targetNet <= 0) {
            $this->addError('target_net', 'Indiquez le net convenu.');

            return;
        }

        $gradeId = $this->salary_grade_id !== '' ? (int) $this->salary_grade_id : null;
        $stepId = $this->salary_step_id !== '' ? (int) $this->salary_step_id : null;

        if ($gradeId) {
            SalaryGrade::whereHas('grid')->findOrFail($gradeId);
        }

        if ($stepId) {
            SalaryStep::whereHas('grade.grid')->findOrFail($stepId);
        }

        $employee = Employee::findOrFail((int) $this->employeeId);
        $from = Carbon::parse($this->effective_from)->startOfDay();

        if (EmployeeSalary::where('employee_id', $employee->id)->whereDate('effective_from', '>=', $from->toDateString())->exists()) {
            $this->addError('effective_from', 'Une ligne de salaire existe déjà à cette date ou après. Choisissez une date plus récente : l\'historique n\'est jamais modifié.');

            return;
        }

        $salary = DB::transaction(function () use ($employee, $from, $gradeId, $stepId, $base, $supplement, $targetNet) {
            // On ferme la ligne encore ouverte la veille du nouveau salaire ; rien n'est supprimé ni réécrit.
            EmployeeSalary::where('employee_id', $employee->id)
                ->whereDate('effective_from', '<', $from->toDateString())
                ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from->toDateString()))
                ->update(['effective_until' => $from->copy()->subDay()->toDateString()]);

            $salary = new EmployeeSalary([
                'employee_id' => $employee->id,
                'salary_grade_id' => $gradeId,
                'salary_step_id' => $stepId,
                'mode' => $this->mode,
                'base_amount' => $base,
                'supplement_amount' => $supplement,
                'target_net' => $this->mode === 'net_target' ? $targetNet : null,
                'effective_from' => $from->toDateString(),
                'effective_until' => null,
                'notes' => trim($this->notes) ?: null,
                'created_by' => auth()->id(),
            ]);
            $salary->company_id = $this->companyId();
            $salary->save();

            return $salary;
        });

        $this->audit('create', 'employee_salaries', $salary->id, null, [
            'employee_id' => $employee->id, 'mode' => $this->mode, 'base' => $base, 'supplement' => $supplement,
            'target_net' => $this->mode === 'net_target' ? $targetNet : null, 'effective_from' => $from->toDateString(),
        ]);

        $this->showForm = false;
        $this->flash = 'Salaire enregistré pour ' . $this->employeeLabel . ' à partir du ' . $from->format('d/m/Y') . '.';
    }

    // ───── Initialisation depuis les contrats ─────

    public function initFromContracts(): void
    {
        $this->allow('update', 'employee_salaries');

        $missing = Employee::whereNotIn('id', EmployeeSalary::select('employee_id'))->pluck('id');

        if ($missing->isEmpty()) {
            $this->flash = 'Tous les employés ont déjà un salaire enregistré : rien à initialiser.';

            return;
        }

        $contracts = Contract::whereIn('employee_id', $missing)->where('base_salary', '>', 0)
            ->orderByDesc('start_date')->get()->groupBy('employee_id');

        $created = 0;

        DB::transaction(function () use ($contracts, &$created) {
            foreach ($contracts as $employeeId => $list) {
                $contract = $list->firstWhere('status', 'active') ?? $list->first();
                $start = $contract->start_date ? Carbon::parse($contract->start_date)->toDateString() : now()->toDateString();

                $salary = new EmployeeSalary([
                    'employee_id' => (int) $employeeId,
                    'mode' => 'gross',
                    'base_amount' => (int) round((float) $contract->base_salary),
                    'supplement_amount' => 0,
                    'effective_from' => $start,
                    'notes' => 'Initialisé depuis le contrat',
                    'created_by' => auth()->id(),
                ]);
                $salary->company_id = $this->companyId();
                $salary->save();
                $created++;
            }
        });

        $skipped = $missing->count() - $created;
        $this->audit('create', 'employee_salaries', null, null, ['init_from_contracts' => $created]);
        $this->flash = $created . ' salaire(s) initialisé(s) depuis les contrats'
            . ($skipped > 0 ? ' ; ' . $skipped . ' employé(s) sans contrat exploitable (salaire de base absent ou nul) restent à saisir à la main.' : '.');
    }

    // ───── Historique ─────

    public function showHistory(int $employeeId): void
    {
        $this->historyId = Employee::findOrFail($employeeId)->id;
    }

    public function closeHistory(): void
    {
        $this->historyId = null;
    }

    public function render()
    {
        $today = now()->toDateString();
        $term = mb_strtolower(trim($this->search));

        $current = EmployeeSalary::inForceOn($today)->orderBy('effective_from')->get()->keyBy('employee_id');

        $rows = Employee::orderBy('matricule')->get()
            ->map(fn ($e) => ['employee' => $e, 'name' => $this->employeeName($e), 'salary' => $current->get($e->id)])
            ->filter(fn ($r) => $term === '' || str_contains(mb_strtolower($r['name'] . ' ' . $r['employee']->matricule), $term))
            ->values();

        $history = $this->historyId
            ? EmployeeSalary::where('employee_id', $this->historyId)->with(['grade', 'step'])->orderByDesc('effective_from')->get()
            : collect();

        $historyEmployee = $this->historyId ? Employee::find($this->historyId) : null;

        return view('livewire.payroll.employee-salaries', [
            'rows' => $rows->take(300),
            'total' => $rows->count(),
            'withoutSalary' => $rows->filter(fn ($r) => ! $r['salary'])->count(),
            'history' => $history,
            'historyName' => $historyEmployee ? $this->employeeName($historyEmployee) : '',
            'grades' => SalaryGrade::whereHas('grid', fn ($q) => $q->where('is_active', true))->with(['grid', 'steps'])->orderBy('sort')->orderBy('id')->get(),
            'canEdit' => $this->allowed('update', 'employee_salaries'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Salaires des employés']);
    }
}
