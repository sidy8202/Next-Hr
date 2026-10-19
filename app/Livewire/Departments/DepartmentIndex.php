<?php

namespace App\Livewire\Departments;

use App\Livewire\Concerns\AuthorizesResource;
use App\Livewire\Concerns\WithDataTable;
use App\Models\Department;
use App\Services\AuditLogger;
use App\Services\DepartmentService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Départements')]
class DepartmentIndex extends Component
{
    use AuthorizesResource, WithDataTable;

    #[Url(as: 'f')]
    public array $filters = ['status' => ''];

    protected function sortableColumns(): array
    {
        return ['code', 'name', 'employees_count', 'is_active'];
    }

    public function mount(): void
    {
        $this->authorizeAction('read', 'departments');
    }

    /** « Modifier » : ouvre la PAGE de modification du département coché. */
    public function edit()
    {
        $id = (int) (array_values($this->selected)[0] ?? 0);
        $department = Department::findOrFail($id);
        $this->authorizeAction('update', 'departments', $department);

        return $this->redirectRoute('departments.edit', ['department' => $id], navigate: true);
    }

    public function setActive(bool $active): void
    {
        $departments = $this->selectedDepartments();

        foreach ($departments as $department) {
            $this->authorizeAction('update', 'departments', $department);
        }

        Department::whereIn('id', $departments->pluck('id'))->update(['is_active' => $active]);

        $this->dispatch('notify', type: 'success', message: $departments->count().($active ? ' département(s) activé(s).' : ' département(s) désactivé(s).'));
        $this->selected = [];
    }

    public function deleteSelected(DepartmentService $service): void
    {
        $deleted = 0;
        $blockers = [];

        foreach ($this->selectedDepartments() as $department) {
            $this->authorizeAction('delete', 'departments', $department);

            if ($reason = $service->deletionBlocker($department)) {
                $blockers[] = $reason;

                continue;
            }

            $department->delete();
            AuditLogger::log('deleted', 'departments', $department->id, ['name' => $department->name], null);
            $deleted++;
        }

        if ($deleted) {
            $this->dispatch('notify', type: 'success', message: "{$deleted} département(s) supprimé(s).");
        }

        if ($blockers) {
            $this->dispatch('notify', type: 'error', message: $blockers[0].(count($blockers) > 1 ? ' (+ '.(count($blockers) - 1).' autre(s))' : ''));
        }

        $this->selected = [];
    }

    public function export()
    {
        $this->authorizeAction('read', 'departments');
        $query = $this->query();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code', 'Département', 'Chef (code)', 'Chef', 'Effectif actif', 'Statut'], ';');

            foreach ($query->lazy(200) as $d) {
                fputcsv($out, [
                    $this->csvSafe($d->code), $this->csvSafe($d->name),
                    $d->head?->matricule, $this->csvSafe($d->head?->full_name),
                    $d->employees_count, $d->is_active ? 'Actif' : 'Inactif',
                ], ';');
            }

            fclose($out);
        }, 'departements-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function selectedDepartments()
    {
        return Department::whereIn('id', $this->selectedIds())->get();
    }

    private function query(): Builder
    {
        $search = trim($this->search);
        $status = $this->filters['status'] ?? '';

        return Department::query()
            ->with('head:id,matricule,first_name,last_name')
            ->withCount(['employees' => fn ($q) => $q->where('status', 'active')])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', $search.'%')->orWhere('name', 'like', '%'.$search.'%')))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderBy($this->orderColumn('name'), $this->orderDirection());
    }

    public function render()
    {
        return view('livewire.departments.department-index', ['rows' => $this->query()->paginate($this->pageSize())]);
    }
}
