<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePayProfile;
use App\Models\PayProfile;
use App\Models\Position;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PayAssignments extends Component
{
    use PayrollGuards;

    public string $search = '';
    public $departmentId = '';
    public $positionId = '';
    public bool $onlyWithout = false;

    /** @var array<int, int|string> */
    public array $selected = [];
    public $profileId = '';
    public string $effective_from = '';
    public string $notes = '';

    public ?int $historyId = null;
    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'pay_profiles');
        $this->effective_from = now()->toDateString();
    }

    public function updated($name): void
    {
        // Un changement de filtre vide la sélection pour éviter d'affecter des employés qu'on ne voit plus.
        if (in_array($name, ['search', 'departmentId', 'positionId', 'onlyWithout'], true)) {
            $this->selected = [];
        }
    }

    public function selectAllVisible(): void
    {
        $this->selected = array_map(fn ($r) => $r['employee']->id, $this->rows()->take(300)->all());
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function assign(): void
    {
        $this->allow('update', 'pay_profiles');

        $this->validate([
            'effective_from' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [], ['effective_from' => "date d'effet"]);

        if ($this->profileId === '' || $this->profileId === null) {
            $this->addError('profileId', 'Choisissez un modèle.');

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $this->selected)));

        if ($ids === []) {
            $this->addError('selected', 'Cochez au moins un employé.');

            return;
        }

        $profile = PayProfile::where('is_active', true)->findOrFail((int) $this->profileId);
        $from = Carbon::parse($this->effective_from)->startOfDay();

        $done = 0;
        $same = 0;
        $conflict = 0;

        DB::transaction(function () use ($ids, $profile, $from, &$done, &$same, &$conflict) {
            foreach (Employee::whereIn('id', $ids)->get() as $employee) {
                if (EmployeePayProfile::where('employee_id', $employee->id)->whereDate('effective_from', '>=', $from->toDateString())->exists()) {
                    $conflict++;

                    continue;
                }

                $current = EmployeePayProfile::where('employee_id', $employee->id)->inForceOn($from->toDateString())->first();

                if ($current && (int) $current->pay_profile_id === (int) $profile->id && $current->effective_until === null) {
                    $same++;

                    continue;
                }

                EmployeePayProfile::where('employee_id', $employee->id)
                    ->whereDate('effective_from', '<', $from->toDateString())
                    ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from->toDateString()))
                    ->update(['effective_until' => $from->copy()->subDay()->toDateString()]);

                $row = new EmployeePayProfile([
                    'employee_id' => $employee->id,
                    'pay_profile_id' => $profile->id,
                    'effective_from' => $from->toDateString(),
                    'effective_until' => null,
                    'notes' => trim($this->notes) ?: null,
                    'created_by' => auth()->id(),
                ]);
                $row->company_id = $this->companyId();
                $row->save();
                $done++;
            }
        });

        $this->audit('create', 'pay_profiles', $profile->id, null, ['assigned' => $done, 'from' => $from->toDateString(), 'employees' => $ids]);

        $this->flash = $done . ' employé(s) affecté(s) au modèle « ' . $profile->name . ' » à partir du ' . $from->format('d/m/Y') . '.'
            . ($same > 0 ? ' ' . $same . ' avaient déjà ce modèle.' : '')
            . ($conflict > 0 ? ' ' . $conflict . ' ignoré(s) : une affectation existe déjà à cette date ou après (l\'historique n\'est jamais modifié).' : '');

        $this->selected = [];
        $this->notes = '';
    }

    public function showHistory(int $employeeId): void
    {
        $this->historyId = Employee::findOrFail($employeeId)->id;
    }

    public function closeHistory(): void
    {
        $this->historyId = null;
    }

    /** @return \Illuminate\Support\Collection<int, array{employee: Employee, name: string, current: ?EmployeePayProfile}> */
    protected function rows()
    {
        $current = EmployeePayProfile::inForceOn(now()->toDateString())->with('profile')->orderBy('effective_from')->get()->keyBy('employee_id');
        $term = mb_strtolower(trim($this->search));

        return Employee::query()
            ->when($this->departmentId !== '', fn ($q) => $q->where('department_id', (int) $this->departmentId))
            ->when($this->positionId !== '', fn ($q) => $q->where('position_id', (int) $this->positionId))
            ->orderBy('matricule')->get()
            ->map(fn ($e) => ['employee' => $e, 'name' => $this->employeeName($e), 'current' => $current->get($e->id)])
            ->filter(fn ($r) => $term === '' || str_contains(mb_strtolower($r['name'] . ' ' . $r['employee']->matricule), $term))
            ->filter(fn ($r) => ! $this->onlyWithout || ! $r['current'])
            ->values();
    }

    protected function labelOf(object $model, array $keys): string
    {
        $attrs = $model->getAttributes();

        foreach ($keys as $key) {
            if (! empty($attrs[$key])) {
                return (string) $attrs[$key];
            }
        }

        return '#' . $model->getKey();
    }

    public function render()
    {
        $rows = $this->rows();

        $history = $this->historyId
            ? EmployeePayProfile::where('employee_id', $this->historyId)->with('profile')->orderByDesc('effective_from')->get()
            : collect();

        $historyEmployee = $this->historyId ? Employee::find($this->historyId) : null;

        return view('livewire.payroll.pay-assignments', [
            'rows' => $rows->take(300),
            'total' => $rows->count(),
            'withoutProfile' => $rows->filter(fn ($r) => ! $r['current'])->count(),
            'departments' => Department::all()->mapWithKeys(fn ($d) => [$d->id => $this->labelOf($d, ['name', 'label', 'title'])])->sort()->all(),
            'positions' => Position::all()->mapWithKeys(fn ($p) => [$p->id => $this->labelOf($p, ['title', 'name', 'label'])])->sort()->all(),
            'profiles' => PayProfile::where('is_active', true)->orderBy('name')->get(),
            'history' => $history,
            'historyName' => $historyEmployee ? $this->employeeName($historyEmployee) : '',
            'canEdit' => $this->allowed('update', 'pay_profiles'),
        ])->layout($this->resolveLayout('company'), ['title' => 'Affectations de modèles']);
    }
}
