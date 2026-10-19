<?php

namespace App\Livewire\Departments;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditLogger;
use App\Services\DepartmentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page de création / modification d'un département.
 *  - Création : tout est saisi puis enregistré d'un coup (transaction).
 *  - Modification : les POSTES s'ajoutent et se retirent IMMÉDIATEMENT (comme sur la page Postes),
 *    donc plus rien ne peut se perdre en route.
 */
#[Layout('components.layouts.app')]
class DepartmentForm extends Component
{
    use AuthorizesResource;

    public ?int $departmentId = null;

    public array $form = [];

    public ?int $newMemberId = null;

    public string $newPosition = '';

    public function mount(?Department $department = null): void
    {
        // Sans paramètre de route, Laravel fournit un modèle vide : on teste donc « exists ».
        if ($department && $department->exists) {
            $this->authorizeAction('update', 'departments', $department);
            $this->departmentId = $department->id;
            $this->form = [
                'name' => $department->name, 'code' => $department->code, 'is_active' => (bool) $department->is_active,
                'head_id' => $department->head_id, 'member_ids' => [], 'positions' => [],
            ];

            return;
        }

        $this->authorizeAction('create', 'departments');
        $this->form = ['name' => '', 'code' => '', 'is_active' => true, 'head_id' => null, 'member_ids' => [], 'positions' => []];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => 'nom', 'form.code' => 'code', 'form.head_id' => 'chef de département',
            'form.member_ids.*' => 'membre', 'form.positions.*' => 'poste', 'newPosition' => 'intitulé du poste',
        ];
    }

    // ---------- Membres (enregistrés avec le formulaire) ----------

    public function updatedNewMemberId($value): void
    {
        if ($value && ! in_array((int) $value, $this->form['member_ids'], true)) {
            $this->form['member_ids'][] = (int) $value;
        }

        $this->newMemberId = null;
    }

    public function removeMember(int $id): void
    {
        $this->form['member_ids'] = array_values(array_filter($this->form['member_ids'], fn ($m) => (int) $m !== $id));
    }

    // ---------- Postes ----------

    public function addPosition(): void
{
    $title = trim($this->newPosition);

    // 1. Validation de présence et de format
    $this->validate([
        'newPosition' => ['required', 'string', 'max:120'],
    ], [
        'newPosition.required' => 'Veuillez saisir l\'intitulé du poste.',
    ]);

    $companyId = auth()->user()->company_id;

    // 2. CAS MODIFICATION : Enregistrement immédiat en base de données
    if ($this->departmentId) {
        $this->authorizeAction('update', 'departments', Department::findOrFail($this->departmentId));

        // Validation d'unicité en BDD (case-insensitive via LOWER)
        $existsInDb = Position::where('company_id', $companyId)
            ->where('department_id', $this->departmentId)
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->exists();

        if ($existsInDb) {
            $this->addError('newPosition', 'Ce poste existe déjà dans ce département.');
            return;
        }

        $position = Position::create([
            'department_id' => $this->departmentId,
            'title' => $title,
        ]);

        AuditLogger::log('created', 'positions', $position->id, null, [
            'title' => $title,
            'department_id' => $this->departmentId,
        ]);

        $this->newPosition = '';
        $this->dispatch('notify', type: 'success', message: "Poste « {$title} » ajouté.");

        return;
    }

    // 3. CAS CRÉATION : Vérification dans le tableau mémoire ($this->form['positions'])
    $alreadyInForm = array_map(fn ($p) => mb_strtolower(trim($p)), $this->form['positions']);

    if (in_array(mb_strtolower($title), $alreadyInForm, true)) {
        $this->addError('newPosition', 'Ce poste a déjà été ajouté dans la liste.');
        return;
    }

    // Ajout dans le tableau si tout est valide
    $this->form['positions'][] = $title;
    $this->newPosition = '';
}

    public function removeStagedPosition(int $index): void
    {
        unset($this->form['positions'][$index]);
        $this->form['positions'] = array_values($this->form['positions']);
    }

    public function deletePosition(int $id): void
    {
        abort_unless($this->departmentId, 404);

        $position = Position::where('department_id', $this->departmentId)->findOrFail($id);
        $this->authorizeAction('delete', 'positions', $position);

        if ($position->employees()->withTrashed()->exists() || DB::table('job_openings')->where('position_id', $position->id)->exists()) {
            $this->dispatch('notify', type: 'error', message: "« {$position->title} » est utilisé (employés ou offres d'emploi) : il ne peut pas être supprimé.");

            return;
        }

        $position->delete();
        $this->dispatch('notify', type: 'success', message: "Poste « {$position->title} » supprimé.");
    }

    // ---------- Enregistrement ----------

    public function save(DepartmentService $service)
    {
        $editing = $this->departmentId ? Department::findOrFail($this->departmentId) : null;
        $this->authorizeAction($editing ? 'update' : 'create', 'departments', $editing);

        $this->form['code'] = Str::upper(trim((string) ($this->form['code'] ?? '')));

        // unique / exists interrogent la base SANS le filtre automatique : on ajoute toujours l'entreprise.
        $cid = auth()->user()->company_id;

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.code' => ['required', 'string', 'max:20',
                Rule::unique('departments', 'code')->where('company_id', $cid)->ignore($this->departmentId)],
            'form.is_active' => ['boolean'],
            'form.head_id' => ['nullable', 'integer',
                Rule::exists('employees', 'id')->where('company_id', $cid)->where('status', 'active')->whereNull('deleted_at')],
            'form.member_ids' => ['array', 'max:200'],
            'form.member_ids.*' => ['integer', Rule::exists('employees', 'id')->where('company_id', $cid)->whereNull('deleted_at')],
            'form.positions' => ['array', 'max:50'],
            'form.positions.*' => ['string', 'max:120', 'distinct'],
        ])['form'];

        try {
            $department = $service->save($editing, $validated);
        } catch (UniqueConstraintViolationException $e) {
            report($e);
            $this->addError('form.code', 'Ce code est déjà utilisé.');

            return;
        }

        AuditLogger::log($editing ? 'updated' : 'created', 'departments', $department->id, null, ['name' => $department->name, 'code' => $department->code]);

        session()->flash('notify', ['type' => 'success', 'message' => "Département « {$department->name} » enregistré."]);

        return $this->redirectRoute('departments.show', ['department' => $department->id], navigate: true);
    }

    public function render()
    {
        $ids = $this->form['member_ids'] ?? [];

        return view('livewire.departments.department-form', [
            'memberEmployees' => $ids
                ? Employee::with('department:id,code')->whereIn('id', $ids)->get(['id', 'matricule', 'first_name', 'last_name', 'department_id'])
                : collect(),
            'existingPositions' => $this->departmentId
                ? Position::where('department_id', $this->departmentId)->withCount('employees')->orderBy('title')->get()
                : collect(),
            'memberCount' => $this->departmentId ? Employee::where('department_id', $this->departmentId)->count() : 0,
        ])->title($this->departmentId ? 'Modifier le département' : 'Nouveau département');
    }
}
