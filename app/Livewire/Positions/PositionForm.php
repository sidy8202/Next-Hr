<?php

namespace App\Livewire\Positions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Position;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
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
                ];

                return;
            }

            $this->authorizeAction('create', 'positions');
            $this->form = ['department_id' => '', 'title' => '', 'grade' => '', 'salary_min' => '', 'salary_max' => ''];
        }

    protected function validationAttributes(): array
    {
        return [
            'form.department_id' => 'département', 'form.title' => 'intitulé', 'form.grade' => 'grade',
            'form.salary_min' => 'salaire minimum', 'form.salary_max' => 'salaire maximum',
        ];
    }

    public function save()
    {
        $editing = $this->positionId ? Position::findOrFail($this->positionId) : null;
        $this->authorizeAction($editing ? 'update' : 'create', 'positions', $editing);

        $cid = auth()->user()->company_id;

        $data = $this->validate([
            'form.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'form.title' => ['required', 'string', 'max:120',
                Rule::unique('positions', 'title')->where('company_id', $cid)->where('department_id', $this->form['department_id'] ?: 0)->ignore($this->positionId)],
            'form.grade' => ['nullable', 'string', 'max:30'],
            'form.salary_min' => ['nullable', 'integer', 'min:0'],
            'form.salary_max' => ['nullable', 'integer', 'min:0', 'gte:form.salary_min'],
        ])['form'];

        $payload = [
            'department_id' => (int) $data['department_id'],
            'title' => trim($data['title']),
            'grade' => $data['grade'] !== '' ? $data['grade'] : null,
            'salary_min' => $data['salary_min'] !== '' ? (int) $data['salary_min'] : null,
            'salary_max' => $data['salary_max'] !== '' ? (int) $data['salary_max'] : null,
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
