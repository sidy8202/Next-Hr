<?php

namespace App\Livewire\Payroll;

use App\Livewire\Payroll\Concerns\PayrollGuards;
use App\Models\Position;
use App\Models\SalaryGrade;
use App\Models\SalaryGrid;
use App\Models\SalaryStep;
use Livewire\Component;

class SalaryGrids extends Component
{
    use PayrollGuards;

    public ?int $gridId = null;
    public bool $showGridForm = false;
    public ?int $editingGridId = null;
    public string $gridName = '';
    public string $gridConvention = '';
    public string $gridFrom = '';
    public bool $gridActive = true;

    public string $newGradeCode = '';
    public string $newGradeLabel = '';

    /** @var array<int, array{label?: string, amount?: string}> nouvel échelon par grade */
    public array $newSteps = [];
    /** @var array<int, string> */
    public array $gradeLabels = [];
    /** @var array<int, string> */
    public array $stepLabels = [];
    /** @var array<int, string> */
    public array $stepAmounts = [];

    public string $flash = '';

    public function mount(): void
    {
        $this->allow('read', 'salary_grids');

        $first = SalaryGrid::orderByDesc('is_active')->orderBy('name')->first();

        if ($first) {
            $this->selectGrid($first->id);
        }
    }

    // ───── Grilles ─────

    public function selectGrid(int $id): void
    {
        $grid = SalaryGrid::findOrFail($id);
        $this->gridId = $grid->id;
        $this->showGridForm = false;
        $this->newSteps = [];
        $this->loadGrid();
    }

    protected function loadGrid(): void
    {
        $this->gradeLabels = [];
        $this->stepLabels = [];
        $this->stepAmounts = [];

        if (! $this->gridId) {
            return;
        }

        $grades = SalaryGrade::where('salary_grid_id', $this->gridId)->with('steps')->orderBy('sort')->orderBy('id')->get();

        foreach ($grades as $grade) {
            $this->gradeLabels[$grade->id] = (string) $grade->label;

            foreach ($grade->steps as $step) {
                $this->stepLabels[$step->id] = (string) $step->label;
                $this->stepAmounts[$step->id] = (string) $step->amount;
            }
        }
    }

    public function newGrid(): void
    {
        $this->allow('update', 'salary_grids');
        $this->resetErrorBag();
        $this->editingGridId = null;
        $this->gridName = '';
        $this->gridConvention = '';
        $this->gridFrom = '';
        $this->gridActive = true;
        $this->showGridForm = true;
    }

    public function editGrid(int $id): void
    {
        $this->allow('update', 'salary_grids');
        $this->resetErrorBag();
        $grid = SalaryGrid::findOrFail($id);
        $this->editingGridId = $grid->id;
        $this->gridName = (string) $grid->name;
        $this->gridConvention = (string) $grid->convention;
        $this->gridFrom = $grid->effective_from?->format('Y-m-d') ?? '';
        $this->gridActive = (bool) $grid->is_active;
        $this->showGridForm = true;
    }

    public function cancelGridForm(): void
    {
        $this->showGridForm = false;
        $this->resetErrorBag();
    }

    public function saveGrid(): void
    {
        $this->allow('update', 'salary_grids');

        $this->validate([
            'gridName' => 'required|string|max:120',
            'gridConvention' => 'nullable|string|max:150',
            'gridFrom' => 'nullable|date',
        ], [], ['gridName' => 'nom de la grille', 'gridConvention' => 'convention', 'gridFrom' => "date d'effet"]);

        $data = [
            'name' => trim($this->gridName),
            'convention' => trim($this->gridConvention) ?: null,
            'effective_from' => $this->gridFrom ?: null,
            'is_active' => $this->gridActive,
        ];

        if ($this->editingGridId) {
            $grid = SalaryGrid::findOrFail($this->editingGridId);
            $old = $grid->only(array_keys($data));
            $grid->update($data);
            $this->audit('update', 'salary_grids', $grid->id, $old, $data);
        } else {
            $grid = new SalaryGrid($data);
            $grid->company_id = $this->companyId();
            $grid->save();
            $this->audit('create', 'salary_grids', $grid->id, null, $data);
        }

        $this->showGridForm = false;
        $this->flash = 'Grille enregistrée.';
        $this->selectGrid($grid->id);
    }

    public function deleteGrid(int $id): void
    {
        $this->allow('delete', 'salary_grids');
        $grid = SalaryGrid::findOrFail($id);
        $this->audit('delete', 'salary_grids', $grid->id, ['name' => $grid->name], null);
        $grid->delete();

        $this->gridId = null;
        $this->gradeLabels = [];
        $this->stepLabels = [];
        $this->stepAmounts = [];
        $this->flash = 'Grille supprimée. Les postes et salaires qui y étaient rattachés gardent leurs montants mais perdent leur grade.';

        $next = SalaryGrid::orderByDesc('is_active')->orderBy('name')->first();

        if ($next) {
            $this->selectGrid($next->id);
        }
    }

    // ───── Catégories (grades) ─────

    public function addGrade(): void
    {
        $this->allow('update', 'salary_grids');

        $this->validate([
            'newGradeCode' => 'required|string|max:20',
            'newGradeLabel' => 'nullable|string|max:120',
        ], [], ['newGradeCode' => 'code de la catégorie', 'newGradeLabel' => 'libellé']);

        $grid = SalaryGrid::findOrFail((int) $this->gridId);
        $code = trim($this->newGradeCode);

        if (SalaryGrade::where('salary_grid_id', $grid->id)->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->exists()) {
            $this->addError('newGradeCode', 'Cette catégorie existe déjà dans la grille.');

            return;
        }

        $grade = SalaryGrade::create([
            'salary_grid_id' => $grid->id,
            'code' => $code,
            'label' => trim($this->newGradeLabel) ?: null,
            'sort' => (int) SalaryGrade::where('salary_grid_id', $grid->id)->max('sort') + 1,
        ]);

        $this->audit('create', 'salary_grids', $grid->id, null, ['grade' => $code]);
        $this->newGradeCode = '';
        $this->newGradeLabel = '';
        $this->loadGrid();
    }

    public function saveGrade(int $id): void
    {
        $this->allow('update', 'salary_grids');
        $grade = SalaryGrade::whereHas('grid')->findOrFail($id);
        $label = trim((string) ($this->gradeLabels[$id] ?? ''));
        $grade->update(['label' => $label !== '' ? mb_substr($label, 0, 120) : null]);
    }

    public function deleteGrade(int $id): void
    {
        $this->allow('delete', 'salary_grids');
        $grade = SalaryGrade::whereHas('grid')->findOrFail($id);
        $this->audit('delete', 'salary_grids', $grade->salary_grid_id, ['grade' => $grade->code], null);
        $grade->delete();
        $this->loadGrid();
    }

    // ───── Échelons ─────

    public function addStep(int $gradeId): void
    {
        $this->allow('update', 'salary_grids');
        $grade = SalaryGrade::whereHas('grid')->findOrFail($gradeId);

        $in = $this->newSteps[$gradeId] ?? [];
        $amount = $this->digits($in['amount'] ?? '');

        if ($amount <= 0) {
            $this->addError("newSteps.$gradeId.amount", 'Indiquez un montant supérieur à 0.');

            return;
        }

        $count = SalaryStep::where('salary_grade_id', $grade->id)->count();
        $label = trim((string) ($in['label'] ?? ''));

        SalaryStep::create([
            'salary_grade_id' => $grade->id,
            'label' => $label !== '' ? mb_substr($label, 0, 60) : 'Échelon ' . ($count + 1),
            'amount' => $amount,
            'sort' => $count + 1,
        ]);
         \App\Services\Payroll\PositionSalaryRange::syncGrade($grade->id);

        $this->audit('update', 'salary_grids', $grade->salary_grid_id, null, ['grade' => $grade->code, 'step_amount' => $amount]);
        unset($this->newSteps[$gradeId]);
        $this->resetErrorBag();
        $this->loadGrid();
    }

    public function saveStep(int $id): void
    {
        $this->allow('update', 'salary_grids');
        $step = SalaryStep::whereHas('grade.grid')->findOrFail($id);

        $amount = $this->digits($this->stepAmounts[$id] ?? '');

        if ($amount <= 0) {
            $this->addError("stepAmounts.$id", 'Montant invalide.');
            $this->stepAmounts[$id] = (string) $step->amount;

            return;
        }

        $label = trim((string) ($this->stepLabels[$id] ?? ''));
        $old = ['label' => $step->label, 'amount' => $step->amount];
        $step->update(['label' => $label !== '' ? mb_substr($label, 0, 60) : $step->label, 'amount' => $amount]);
        $this->stepAmounts[$id] = (string) $amount;
        \App\Services\Payroll\PositionSalaryRange::syncGrade((int) $step->salary_grade_id);
        $this->audit('update', 'salary_grids', $step->grade->salary_grid_id, $old, ['label' => $step->label, 'amount' => $amount]);
    }

    public function deleteStep(int $id): void
    {
        $this->audit('delete', 'salary_grids', $step->grade->salary_grid_id, ['step' => $step->label, 'amount' => $step->amount], null);
        $gradeId = (int) $step->salary_grade_id;
        $step->delete();
        \App\Services\Payroll\PositionSalaryRange::syncGrade($gradeId);
        $this->loadGrid();
    }

    // ───── Postes ─────

    public function assignPosition(int $positionId, $gradeId): void
    {
        $this->allow('update', 'salary_grids');
        $position = Position::findOrFail($positionId);
        $gradeId = $gradeId === '' || $gradeId === null ? null : (int) $gradeId;

        if ($gradeId !== null) {
            SalaryGrade::whereHas('grid')->findOrFail($gradeId);
        }

        $old = $position->forceFill(['salary_grade_id' => $gradeId])->save();
        \App\Services\Payroll\PositionSalaryRange::syncPosition($position);
        $this->audit('update', 'salary_grids', $position->id, ['position_grade' => $old], ['position_grade' => $gradeId]);
    }

    public function render()
    {
        return view('livewire.payroll.salary-grids', [
            'canEdit' => $this->allowed('update', 'salary_grids'),
            'canDelete' => $this->allowed('delete', 'salary_grids'),
            'grids' => SalaryGrid::withCount('grades')->orderByDesc('is_active')->orderBy('name')->get(),
            'selected' => $this->gridId ? SalaryGrid::with('grades.steps')->find($this->gridId) : null,
            'gradeOptions' => SalaryGrid::with('grades')->orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
        ])->layout($this->resolveLayout('company'), ['title' => 'Grilles salariales']);
    }
}
