<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\EmployeeService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Saisie de plusieurs recrues d'un coup. Chaque ligne a SA date de début, SON type et SA durée de contrat.
 * L'en-tête propose des valeurs par défaut ; avec « même date pour tous », la date commune s'applique à toutes les lignes.
 */
#[Layout('components.layouts.app')]
#[Title('Nouvelles recrues')]
class EmployeeBatch extends Component
{
    use AuthorizesResource;

    public array $batch = [];

    public array $rows = [];

    public array $duplicates = [];

    public bool $confirmDuplicates = false;

    public bool $created = false;

    public array $credentials = [];

    public array $mailStatus = [];

    public int $createdCount = 0;

    public function mount(): void
    {
        $this->authorizeAction('create', 'employees');

        $this->batch = [
            'department_id' => null, 'same_date' => true, 'hire_date' => now()->toDateString(),
            'contract_type' => 'CDD', 'duration_months' => '6',
        ];
        $this->rows = [$this->blankRow(), $this->blankRow()];
    }

    private function blankRow(): array
    {
        return [
            'first_name' => '', 'last_name' => '', 'gender' => '', 'birth_date' => '', 'position_id' => '',
            'hire_date' => $this->batch['hire_date'] ?? now()->toDateString(),
            'contract_type' => $this->batch['contract_type'] ?? 'CDD',
            'duration_months' => $this->batch['duration_months'] ?? '6',
            'base_salary' => '', 'create_account' => false, 'email' => '',
        ];
    }

    public function addRow(): void
    {
        if (count($this->rows) < 50) {
            $this->rows[] = $this->blankRow();
        }
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows) ?: [$this->blankRow()];
    }

    /** Copie type et durée de l'en-tête dans toutes les lignes. */
    public function applyContractToAll(): void
    {
        foreach ($this->rows as $i => $row) {
            $this->rows[$i]['contract_type'] = $this->batch['contract_type'];
            $this->rows[$i]['duration_months'] = $this->batch['duration_months'];
        }
    }

    public function updatedBatchSameDate(): void
    {
        $this->syncDates();
    }

    public function updatedBatchHireDate(): void
    {
        $this->syncDates();
    }

    public function updatedBatchDepartmentId(): void
    {
        foreach ($this->rows as $i => $row) {
            $this->rows[$i]['position_id'] = '';
        }
    }

    private function syncDates(): void
    {
        if (! empty($this->batch['same_date'])) {
            foreach ($this->rows as $i => $row) {
                $this->rows[$i]['hire_date'] = $this->batch['hire_date'];
            }
        }
    }

    #[Computed]
    public function departments()
    {
        return Department::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function positions()
    {
        $id = $this->batch['department_id'] ?? null;

        return $id ? Position::where('department_id', $id)->orderBy('title')->get(['id', 'title']) : collect();
    }

    protected function validationAttributes(): array
    {
        return [
            'batch.department_id' => 'département', 'batch.hire_date' => 'date commune',
            'rows.*.first_name' => 'prénom', 'rows.*.last_name' => 'nom', 'rows.*.birth_date' => 'date de naissance',
            'rows.*.position_id' => 'poste', 'rows.*.hire_date' => 'date de début', 'rows.*.contract_type' => 'type de contrat',
            'rows.*.duration_months' => 'durée', 'rows.*.base_salary' => 'salaire', 'rows.*.email' => 'email',
        ];
    }

    public function save(EmployeeService $service)
    {
        $this->authorizeAction('create', 'employees');
        $this->syncDates();

        $cid = auth()->user()->company_id; // unique / exists ne passent PAS par le filtre automatique
        $departmentId = $this->batch['department_id'] ?: 0;

        $validated = $this->validate([
            'batch.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'batch.hire_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1', 'max:50'],
            'rows.*.first_name' => ['required', 'string', 'max:100'],
            'rows.*.last_name' => ['required', 'string', 'max:100'],
            'rows.*.gender' => ['nullable', Rule::in(['M', 'F'])],
            'rows.*.birth_date' => ['nullable', 'date', 'before:today'],
            'rows.*.position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $cid)->where('department_id', $departmentId)],
            'rows.*.hire_date' => ['required', 'date'],
            'rows.*.contract_type' => ['required', Rule::in(['CDI', 'CDD', 'STAGE'])],
            'rows.*.duration_months' => ['nullable', 'integer', 'min:1', 'max:60', 'required_unless:rows.*.contract_type,CDI'],
            'rows.*.base_salary' => ['required', 'integer', 'min:0'],
            'rows.*.create_account' => ['boolean'],
            'rows.*.email' => ['nullable', 'required_if:rows.*.create_account,true', 'email', 'max:255', 'distinct:ignore_case', Rule::unique('users', 'email')],
        ]);

        $people = array_map(fn (array $r) => $r + [
            'department_id' => (int) $validated['batch']['department_id'],
            'children_count' => 0,
            'login_email' => ! empty($r['create_account']) ? $r['email'] : null,
            'send_to' => ! empty($r['create_account']) ? [$r['email']] : [],
        ], $validated['rows']);

        if (! $this->confirmDuplicates && ($found = $this->findDuplicates($people))) {
            $this->duplicates = $found;

            return;
        }

        try {
            $result = $service->createMany($people);
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            $cause = str_contains($e->getMessage(), 'users_email') ? 'Un email de compte existe déjà.'
                : (str_contains($e->getMessage(), 'matricule') ? 'Un matricule existe déjà.' : 'Une valeur qui doit être unique existe déjà.');

            $this->addError('save', "Enregistrement annulé : {$cause} Aucune recrue n'a été créée.");

            return;
        }

        $this->createdCount = count($result['employees']);

        if ($result['credentials']) {
            $this->mailStatus = $service->sendCredentials($result['credentials']);
            $this->credentials = $result['credentials'];
            $this->created = true;

            return;
        }

        session()->flash('notify', ['type' => 'success', 'message' => "{$this->createdCount} employé(s) enregistré(s)."]);

        return $this->redirectRoute('employees.index', navigate: true);
    }

    public function saveAnyway(EmployeeService $service)
    {
        $this->confirmDuplicates = true;

        return $this->save($service);
    }

    private function findDuplicates(array $people): array
    {
        $messages = [];
        $seen = [];

        foreach (array_values($people) as $i => $p) {
            if (empty($p['birth_date'])) {
                continue;
            }

            $name = trim($p['first_name']).' '.trim($p['last_name']);
            $key = Str::lower($name.'|'.$p['birth_date']);

            if (isset($seen[$key])) {
                $messages[] = 'Ligne '.($i + 1)." : « {$name} » apparaît deux fois dans ce lot.";

                continue;
            }

            $seen[$key] = true;

            $existing = Employee::where('first_name', trim($p['first_name']))->where('last_name', trim($p['last_name']))
                ->whereDate('birth_date', $p['birth_date'])->first(['matricule']);

            if ($existing) {
                $messages[] = "« {$name} » ressemble à l'employé {$existing->matricule} (même nom et même date de naissance).";
            }
        }

        return $messages;
    }

    public function render()
    {
        return view('livewire.employees.employee-batch');
    }
}
