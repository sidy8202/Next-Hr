<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Livewire\Concerns\WithDataTable;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Employés')]
class EmployeeIndex extends Component
{
    use AuthorizesResource, WithDataTable;

    #[Url(as: 'f')]
    public array $filters = ['department' => '', 'status' => '', 'expat' => ''];

    protected function sortableColumns(): array
    {
        return ['matricule', 'last_name', 'hire_date', 'status'];
    }

    public function mount(): void
    {
        $this->authorizeAction('read', 'employees');
    }

    #[On('employees-changed')]
    public function refreshList(): void
    {
        $this->selected = [];
    }

    public function editSelected()
    {
        $id = (int) (array_values($this->selected)[0] ?? 0);
        $employee = Employee::visibleTo(auth()->user())->findOrFail($id);
        $this->authorizeAction('update', 'employees', $employee);

        return $this->redirectRoute('employees.edit', ['employee' => $id], navigate: true);
    }

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['active', 'suspended'], true), 422);

        $employees = $this->selectedEmployees();

        foreach ($employees as $employee) {
            $this->authorizeAction('update', 'employees', $employee);
        }

        // Un employé sorti (terminated) ne se réactive pas ici.
        Employee::whereIn('id', $employees->pluck('id'))->where('status', '!=', 'terminated')->update(['status' => $status]);
        AuditLogger::log('status_changed', 'employees', null, null, ['ids' => $employees->pluck('id')->all(), 'status' => $status]);

        $this->dispatch('notify', type: 'success', message: $status === 'active' ? 'Employés réactivés.' : 'Employés suspendus.');
        $this->selected = [];
    }

    public function deleteSelected(): void
    {
        $deleted = 0;
        $blocked = [];

        foreach ($this->selectedEmployees() as $employee) {
            $this->authorizeAction('delete', 'employees', $employee);

            if (Department::where('head_id', $employee->id)->exists()) {
                $blocked[] = "{$employee->matricule} est chef de département : désignez un autre chef d'abord.";

                continue;
            }

            if (Employee::where('manager_id', $employee->id)->exists()) {
                $blocked[] = "{$employee->matricule} a des subordonnés : réaffectez-les d'abord.";

                continue;
            }

            DB::transaction(function () use ($employee) {
                $employee->delete(); // suppression douce : récupérable
                User::where('employee_id', $employee->id)->update(['is_active' => false]);
                AuditLogger::log('deleted', 'employees', $employee->id, ['matricule' => $employee->matricule], null);
            });
            $deleted++;
        }

        if ($deleted) {
            $this->dispatch('notify', type: 'success', message: "{$deleted} employé(s) retiré(s) de la liste (comptes désactivés).");
        }

        if ($blocked) {
            $this->dispatch('notify', type: 'error', message: $blocked[0].(count($blocked) > 1 ? ' (+ '.(count($blocked) - 1).' autre(s))' : ''));
        }

        $this->selected = [];
    }

    public function export()
    {
        $this->authorizeAction('read', 'employees');
        $query = $this->query();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code', 'Nom', 'Prénom', 'Département', 'Poste', "Date d'embauche", 'Statut'], ';');

            foreach ($query->lazy(200) as $e) {
                fputcsv($out, [
                    $e->matricule, $this->csvSafe($e->last_name), $this->csvSafe($e->first_name),
                    $e->department?->code, $this->csvSafe($e->position?->title), $e->hire_date?->format('d/m/Y'), $e->status,
                ], ';');
            }

            fclose($out);
        }, 'employes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function selectedEmployees()
    {
        // visibleTo : un utilisateur ne peut agir que sur les employés qu'il a le droit de voir
        return Employee::visibleTo(auth()->user())->whereIn('id', $this->selectedIds())->get();
    }

    private function query(): Builder
    {
        $search = trim($this->search);
        $department = $this->filters['department'] ?? '';
        $status = $this->filters['status'] ?? '';
        $expat = $this->filters['expat'] ?? '';

        return Employee::visibleTo(auth()->user())
            ->with(['department:id,code,name', 'position:id,title'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('matricule', 'like', $search.'%')
                ->orWhere('first_name', 'like', $search.'%')
                ->orWhere('last_name', 'like', $search.'%')
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ['%'.$search.'%'])))
            ->when($department !== '', fn ($q) => $q->where('department_id', (int) $department))
            ->when(in_array($status, ['active', 'suspended', 'terminated'], true), fn ($q) => $q->where('status', $status))
            ->when($expat === '1', fn ($q) => $q->where('is_expatriate', true))
            ->orderBy($this->orderColumn('last_name'), $this->orderDirection());
    }

    public function render()
    {
        return view('livewire.employees.employee-index', [
            'rows' => $this->query()->paginate($this->pageSize()),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }
}
