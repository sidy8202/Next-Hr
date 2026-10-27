<?php

namespace App\Livewire\Assignments;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSite;
use App\Services\AssignmentService;
use App\Services\AuditLogger;
use App\Support\LeaveVisibility;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Affectations et intérims : poste occupé en plus du poste principal, dans un autre département ou une autre base. */
#[Layout('components.layouts.app')]
#[Title('Affectations et intérims')]
class AssignmentIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'etat')]
    public string $state = 'active'; // active | upcoming | past | all

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;
    public $employee_id = '';
    public string $type = 'interim';
    public $department_id = '';
    public $position_id = '';
    public $work_site_id = '';
    public $reports_to_employee_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public $allocation_percent = '';
    public $allowance_amount = '';
    public string $allowance_basis = 'monthly';
    public bool $acts_as_head = false;
    public bool $manager_validates = true;
    public string $reason = '';

    public string $site_name = '';
    public string $site_city = '';
    public $base_employee_id = '';
    public $base_site_id = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'assignments');
    }

    #[Computed]
    public function canManage(): bool
    {
        return LeaveVisibility::isHr(auth()->user(), 'assignments');
    }

    public function updated($name): void
    {
        if (in_array($name, ['state', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function updatedDepartmentId(): void
    {
        $this->position_id = '';
        unset($this->positions);
    }

    #[Computed]
    public function assignments()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'assignments');
        $term = trim($this->search);

        return Assignment::with(['employee:id,matricule,first_name,last_name', 'department', 'position', 'site', 'reportsTo:id,first_name,last_name'])
            ->when($ids !== null, fn ($q) => $q->whereIn('employee_id', $ids))
            ->when($this->state === 'active', fn ($q) => $q->active())
            ->when($this->state === 'upcoming', fn ($q) => $q->whereDate('start_date', '>', today()))
            ->when($this->state === 'past', fn ($q) => $q->whereNotNull('end_date')->whereDate('end_date', '<', today()))
            ->when($term !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('matricule', 'like', "{$term}%")))
            ->orderByDesc('start_date')->paginate(15);
    }

    #[Computed]
    public function employees()
    {
        return $this->canManage ? Employee::where('status', 'active')->orderBy('last_name')->get(['id', 'matricule', 'first_name', 'last_name', 'work_site_id']) : collect();
    }

    #[Computed]
    public function departments()
    {
        return Department::get()->sortBy(fn ($d) => $d->name ?? '')->values();
    }

    #[Computed]
    public function positions()
    {
        return $this->department_id
            ? Position::where('department_id', (int) $this->department_id)->get()->sortBy(fn ($p) => $p->title ?? $p->name ?? '')->values()
            : collect();
    }

    #[Computed]
    public function sites()
    {
        return WorkSite::orderBy('name')->get();
    }

    public function save(): void
    {
        abort_unless($this->canManage, 403);
        $companyId = auth()->user()->company_id;

        $this->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'type' => ['required', Rule::in(array_keys(Assignment::TYPES))],
            'department_id' => ['required'],
            'position_id' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allocation_percent' => ['nullable', 'integer', 'between:1,100'],
            'allowance_amount' => ['nullable', 'numeric', 'min:0'],
            'allowance_basis' => ['required', 'in:monthly,total'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], ['employee_id' => 'employé', 'department_id' => 'département', 'position_id' => 'poste', 'start_date' => 'date de début', 'end_date' => 'date de fin']);

        app(AssignmentService::class)->create(Employee::findOrFail($this->employee_id), $this->only([
            'type', 'department_id', 'position_id', 'work_site_id', 'reports_to_employee_id', 'start_date', 'end_date',
            'allocation_percent', 'allowance_amount', 'allowance_basis', 'acts_as_head', 'manager_validates', 'reason',
        ]));

        $this->reset(['employee_id', 'department_id', 'position_id', 'work_site_id', 'reports_to_employee_id', 'start_date', 'end_date', 'allocation_percent', 'allowance_amount', 'reason', 'acts_as_head']);
        $this->manager_validates = true;
        $this->showForm = false;
        unset($this->assignments, $this->positions);
        $this->dispatch('notify', type: 'success', message: 'Affectation enregistrée : l’employé en est informé.');
    }

    public function end(int $id): void
    {
        abort_unless($this->canManage, 403);
        app(AssignmentService::class)->end(Assignment::findOrFail($id));
        unset($this->assignments);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage, 403);
        app(AssignmentService::class)->delete(Assignment::findOrFail($id));
        unset($this->assignments);
    }

    public function addSite(): void
    {
        abort_unless($this->canManage, 403);

        $this->validate([
            'site_name' => ['required', 'string', 'max:100', Rule::unique('work_sites', 'name')->where('company_id', auth()->user()->company_id)],
            'site_city' => ['nullable', 'string', 'max:100'],
        ], [], ['site_name' => 'nom de la base']);

        WorkSite::create(['name' => trim($this->site_name), 'city' => trim($this->site_city) ?: null, 'active' => true]);
        $this->reset(['site_name', 'site_city']);
        unset($this->sites);
    }

    public function deleteSite(int $id): void
    {
        abort_unless($this->canManage, 403);
        WorkSite::findOrFail($id)->delete(); // les affectations et employés rattachés repassent à « sans base »
        unset($this->sites);
    }

    /** Base principale d'un employé (sa base d'origine). */
    public function setBase(): void
    {
        abort_unless($this->canManage, 403);

        $this->validate(['base_employee_id' => ['required']], [], ['base_employee_id' => 'employé']);
        $employee = Employee::findOrFail($this->base_employee_id);
        $site = $this->base_site_id ? WorkSite::findOrFail($this->base_site_id) : null;

        Employee::whereKey($employee->id)->update(['work_site_id' => $site?->id]);
        AuditLogger::log('updated', 'assignments', $employee->id, null, ['work_site_id' => $site?->id]);

        unset($this->employees);
        $this->dispatch('notify', type: 'success', message: 'Base principale enregistrée.');
    }

    public function render()
    {
        return view('livewire.assignments.assignment-index');
    }
}
