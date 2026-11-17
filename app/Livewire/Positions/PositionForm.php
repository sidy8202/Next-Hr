<?php

namespace App\Livewire\Positions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Position;
use App\Models\SalaryGrade;
use App\Services\AuditLogger;
use App\Services\Payroll\PositionSalaryRange;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
/**
 * Un poste peut avoir une CATÉGORIE salariale (grille de la paie) : sa fourchette est alors la « base conventionnelle »,
 * calculée à partir des échelons de la catégorie, et non modifiable à la main. Sans catégorie, la fourchette reste libre.
 * Choisir ou changer la catégorie demande le droit de modifier les grilles salariales (comme dans l'écran des grilles).
 */
class PositionForm extends Component
{
    use AuthorizesResource;

    public ?int $positionId = null;

    public array $form = [];

        public function mount(?Position $position = null): void
        {
            if ($position && $position->exists) {
                $this->authorizeAction('update', 'positions', $position);
                $this->positionId = $position->id;
                $this->form = [
                    'department_id' => $position->department_id, 'title' => $position->title, 'grade' => (string) $position->grade,
                    'salary_min' => $position->salary_min ?? '', 'salary_max' => $position->salary_max ?? '',
                    'salary_grade_id' => $position->salary_grade_id ?? '',
                ];

                return;
            }

            $this->authorizeAction('create', 'positions');
            $this->form = ['department_id' => '', 'title' => '', 'grade' => '', 'salary_min' => '', 'salary_max' => '', 'salary_grade_id' => ''];
        }

    /** Catégories de toutes les grilles de l'entreprise, avec leurs échelons. */
    #[Computed]
    public function grades()
    {
        return SalaryGrade::whereHas('grid')->with(['grid:id,name', 'steps:id,salary_grade_id,amount'])->orderBy('salary_grid_id')->orderBy('sort')->orderBy('id')->get();
    }

    #[Computed]
    public function canSetGrade(): bool
    {
        return auth()->user()->canDo('update', 'salary_grids');
    }

    /** Fourchette de la catégorie choisie, ou null (aucune catégorie, ou catégorie sans échelon). @return ?array{min: int, max: int} */
    #[Computed]
    public function gradeRange(): ?array
    {
        $id = $this->form['salary_grade_id'] ?? '';

        return $id === '' || $id === null ? null : PositionSalaryRange::range($this->grades->firstWhere('id', (int) $id)?->steps->pluck('amount')->all() ?? []);
    }

    /** Une catégorie choisie remplit la fourchette avec la base conventionnelle (les champs sont alors verrouillés). */
    public function updatedFormSalaryGradeId(): void
    {
        $this->authorizeGradeChange();

        if (($this->form['salary_grade_id'] ?? '') !== '') {
            $range = $this->gradeRange;
            $this->form['salary_min'] = $range['min'] ?? '';
            $this->form['salary_max'] = $range['max'] ?? '';
        }
    }

    private function authorizeGradeChange(): void
    {
        abort_unless($this->canSetGrade, 403);
    }

    protected function validationAttributes(): array
    {
        return [
            'form.department_id' => 'département', 'form.title' => 'intitulé', 'form.grade' => 'grade',
            'form.salary_min' => 'salaire minimum', 'form.salary_max' => 'salaire maximum', 'form.salary_grade_id' => 'catégorie salariale',
        ];
    }

    public function save()
    {
        $editing = $this->positionId ? Position::findOrFail($this->positionId) : null;
        $this->authorizeAction($editing ? 'update' : 'create', 'positions', $editing);

        $cid = auth()->user()->company_id;

        // Sans le droit sur les grilles, la catégorie du poste ne change pas, quoi qu'envoie le navigateur.
        $gradeId = $this->canSetGrade ? (($this->form['salary_grade_id'] ?? '') !== '' ? (int) $this->form['salary_grade_id'] : null) : $editing?->salary_grade_id;

        if ($gradeId !== null && ! SalaryGrade::whereHas('grid')->whereKey($gradeId)->exists()) {
            $this->addError('form.salary_grade_id', 'Catégorie salariale introuvable.');

            return;
        }

        $range = $gradeId !== null ? PositionSalaryRange::range(\App\Models\SalaryStep::where('salary_grade_id', $gradeId)->pluck('amount')->all()) : null;

        $data = $this->validate([
            'form.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'form.title' => ['required', 'string', 'max:120',
                Rule::unique('positions', 'title')->where('company_id', $cid)->where('department_id', $this->form['department_id'] ?: 0)->ignore($this->positionId)],
            'form.grade' => ['nullable', 'string', 'max:30'],
            'form.salary_min' => $gradeId !== null ? ['nullable'] : ['nullable', 'integer', 'min:0'],
            'form.salary_max' => $gradeId !== null ? ['nullable'] : ['nullable', 'integer', 'min:0', 'gte:form.salary_min'],
            'form.salary_grade_id' => ['nullable'],
        ])['form'];

        $payload = [
            'department_id' => (int) $data['department_id'],
            'title' => trim($data['title']),
            'grade' => $data['grade'] !== '' ? $data['grade'] : null,
            // Avec une catégorie : base conventionnelle (échelons) ; sans : fourchette libre saisie à la main.
            'salary_min' => $gradeId !== null ? ($range['min'] ?? null) : ($data['salary_min'] !== '' ? (int) $data['salary_min'] : null),
            'salary_max' => $gradeId !== null ? ($range['max'] ?? null) : ($data['salary_max'] !== '' ? (int) $data['salary_max'] : null),
            'salary_grade_id' => $gradeId,
        ];

        $position = DB::transaction(function () use ($editing, $payload) {
            $position = $editing ?? new Position();
            $position->fill($payload)->save();

            return $position;
        });

        AuditLogger::log($editing ? 'updated' : 'created', 'positions', $position->id, null, $payload);

        session()->flash('notify', ['type' => 'success', 'message' => "Poste « {$position->title} » enregistré."]);

        return $this->redirectRoute('positions.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.positions.position-form', [
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
        ])->title($this->positionId ? 'Modifier le poste' : 'Nouveau poste');
    }
}
