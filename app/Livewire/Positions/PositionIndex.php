<?php

namespace App\Livewire\Positions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Livewire\Concerns\WithDataTable;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Postes')]
class PositionIndex extends Component
{
    use AuthorizesResource, WithDataTable;

    #[Url(as: 'f')]
    public array $filters = ['department' => ''];

    protected function sortableColumns(): array
    {
        return ['title', 'grade', 'employees_count'];
    }

    public function mount(): void
    {
        $this->authorizeAction('read', 'positions');
    }

    public function edit()
    {
        $id = (int) (array_values($this->selected)[0] ?? 0);
        $position = Position::findOrFail($id);
        $this->authorizeAction('update', 'positions', $position);

        return $this->redirectRoute('positions.edit', ['position' => $id], navigate: true);
    }

    public function deleteSelected(): void
    {
        $deleted = 0;
        $blocked = [];

        foreach (Position::whereIn('id', $this->selectedIds())->get() as $position) {
            $this->authorizeAction('delete', 'positions', $position);

            if ($position->employees()->withTrashed()->exists() || DB::table('job_openings')->where('position_id', $position->id)->exists()) {
                $blocked[] = "« {$position->title} » est utilisé (employés ou offres d'emploi).";

                continue;
            }

            $position->delete();
            $deleted++;
        }

        if ($deleted) {
            $this->dispatch('notify', type: 'success', message: "{$deleted} poste(s) supprimé(s).");
        }

        if ($blocked) {
            $this->dispatch('notify', type: 'error', message: $blocked[0].(count($blocked) > 1 ? ' (+ '.(count($blocked) - 1).' autre(s))' : ''));
        }

        $this->selected = [];
    }

    private function query(): Builder
    {
        $search = trim($this->search);
        $department = $this->filters['department'] ?? '';

        return Position::query()
            ->with('department:id,code,name')
            ->withCount('employees')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->when($department !== '', fn ($q) => $q->where('department_id', (int) $department))
            ->orderBy($this->orderColumn('title'), $this->orderDirection());
    }

    public function render()
    {
        return view('livewire.positions.position-index', [
            'rows' => $this->query()->paginate($this->pageSize()),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }
}
