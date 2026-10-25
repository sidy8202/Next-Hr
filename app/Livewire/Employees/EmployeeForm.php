<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditLogger;
use App\Services\EmployeeService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Page « Nouvel employé » (création) et « Modifier l'employé » (édition).
 * Flux : vue → save() → validation → EmployeeService (transaction) → emails → redirection.
 */
#[Layout('components.layouts.app')]
class EmployeeForm extends Component
{
    use AuthorizesResource, WithFileUploads;

    public ?int $employeeId = null;

    public array $form = [];

    /** Pièces jointes : [['type' => '', 'title' => '', 'file' => null], …] */
    public array $docs = [];

    public array $duplicates = [];

    public bool $confirmDuplicates = false;

    // Écran récapitulatif après création avec accès
    public bool $created = false;

    public ?int $createdId = null;

    public array $credentials = [];

    public array $mailStatus = [];

    public function mount(?Employee $employee = null): void
    {
        $this->docs = [$this->blankDoc()];

        if ($employee && $employee->exists) {
            $this->authorizeAction('update', 'employees', $employee);
            $this->employeeId = $employee->id;
            $this->form = [
                'first_name' => $employee->first_name, 'last_name' => $employee->last_name,
                'gender' => (string) $employee->gender, 'birth_date' => $employee->birth_date?->toDateString() ?? '',
                'birth_place' => (string) $employee->birth_place, 'nationality' => (string) $employee->nationality,
                'national_id' => (string) $employee->national_id, 'marital_status' => (string) $employee->marital_status,
                'spouse_works' => (bool) $employee->spouse_works, 'children_count' => (int) $employee->children_count,
                'is_expatriate' => (bool) $employee->is_expatriate, 'work_permit_number' => (string) $employee->work_permit_number,
                'work_permit_expires_at' => $employee->work_permit_expires_at?->toDateString() ?? '',
                'phone' => (string) $employee->phone, 'work_email' => (string) $employee->work_email,
                'personal_email' => (string) $employee->personal_email, 'address' => (string) $employee->address,
                'emergency_contact_name' => (string) $employee->emergency_contact_name,
                'emergency_contact_phone' => (string) $employee->emergency_contact_phone,
                'department_id' => $employee->department_id, 'position_id' => $employee->position_id,
                'manager_id' => $employee->manager_id, 'hire_date' => $employee->hire_date?->toDateString() ?? '',
                'leave_days_per_month' => $employee->leave_days_per_month !== null ? (string) $employee->leave_days_per_month : '',
            ];

            return;
        }

        $this->authorizeAction('create', 'employees');
        $this->form = $this->blankForm();
    }

    private function blankForm(): array
    {
        return [
            'first_name' => '', 'last_name' => '', 'gender' => '', 'birth_date' => '', 'birth_place' => '', 'nationality' => '',
            'national_id' => '', 'marital_status' => '', 'spouse_works' => false, 'children_count' => 0,
            'is_expatriate' => false, 'work_permit_number' => '', 'work_permit_expires_at' => '',
            'phone' => '', 'work_email' => '', 'personal_email' => '', 'address' => '',
            'emergency_contact_name' => '', 'emergency_contact_phone' => '',
            'department_id' => null, 'position_id' => null, 'manager_id' => null, 'hire_date' => now()->toDateString(),
            'contract_type' => 'CDI', 'duration_months' => '', 'base_salary' => '','leave_days_per_month' => '',
            'create_account' => false, 'login_email_source' => 'work', 'send_work' => true, 'send_personal' => true,
        ];
    }

    private function blankDoc(): array
    {
        return ['type' => '', 'title' => '', 'file' => null];
    }

    public function addDoc(): void
    {
        if (count($this->docs) < 10) {
            $this->docs[] = $this->blankDoc();
        }
    }

    public function removeDoc(int $index): void
    {
        unset($this->docs[$index]);
        $this->docs = array_values($this->docs) ?: [$this->blankDoc()];
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form['position_id'] = null;
    }

    public function updatedFormMaritalStatus(): void
    {
        if (($this->form['marital_status'] ?? '') !== 'married') {
            $this->form['spouse_works'] = false;
        }
    }

    // ---------- Données de la vue ----------

    #[Computed]
    public function departments()
    {
        return Department::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function positions()
    {
        $id = $this->form['department_id'] ?? null;

        return $id ? Position::where('department_id', $id)->orderBy('title')->get(['id', 'title']) : collect();
    }

    /** Types de pièces que CET utilisateur a le droit d'ajouter. */
    #[Computed]
    public function uploadableTypes(): array
    {
        $user = auth()->user();

        return collect(config('documents.types'))
            ->filter(fn ($t) => ($t['kind'] ?? '') === 'received')
            ->filter(fn ($t, $key) => $user->canDo('create', EmployeeService::documentResource($key)))
            ->map(fn ($t) => $t['label'])
            ->all();
    }

    protected function validationAttributes(): array
    {
        return [
            'form.first_name' => 'prénom', 'form.last_name' => 'nom', 'form.birth_date' => 'date de naissance',
            'form.department_id' => 'département', 'form.position_id' => 'poste', 'form.hire_date' => "date d'embauche",
            'form.contract_type' => 'type de contrat', 'form.duration_months' => 'durée du contrat', 'form.base_salary' => 'salaire',
            'form.work_email' => 'email professionnel', 'form.personal_email' => 'email personnel',
            'form.children_count' => "nombre d'enfants", 'form.manager_id' => 'manager','form.leave_days_per_month' => 'jours de congé par mois',
            'docs.*.type' => 'type de pièce', 'docs.*.file' => 'fichier', 'docs.*.title' => 'libellé',
        ];
    }

    // ---------- Règles ----------

    private function identityRules(): array
    {
        return [
            'form.first_name' => ['required', 'string', 'max:100'],
            'form.last_name' => ['required', 'string', 'max:100'],
            'form.gender' => ['nullable', Rule::in(['M', 'F'])],
            'form.birth_date' => ['nullable', 'date', 'before:today'],
            'form.birth_place' => ['nullable', 'string', 'max:255'],
            'form.nationality' => ['nullable', 'string', 'max:60'],
            'form.national_id' => ['nullable', 'string', 'max:60'],
            'form.marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'form.spouse_works' => ['nullable', 'boolean'],
            'form.children_count' => ['required', 'integer', 'min:0', 'max:30'],
            'form.is_expatriate' => ['boolean'],
            'form.work_permit_number' => ['nullable', 'string', 'max:255'],
            'form.work_permit_expires_at' => ['nullable', 'date'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.work_email' => ['nullable', 'email', 'max:255'],
            'form.personal_email' => ['nullable', 'email', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'form.leave_days_per_month' => ['nullable', 'numeric', 'between:0,10'],
        ];
    }

    private function docRules(): array
    {
        $received = collect(config('documents.types'))->filter(fn ($t) => ($t['kind'] ?? '') === 'received')->keys()->all();

        return [
            'docs' => ['array', 'max:10'],
            'docs.*.type' => ['nullable', 'required_with:docs.*.file', Rule::in($received)],
            'docs.*.title' => ['nullable', 'string', 'max:150'],
            'docs.*.file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    /** Pièces réellement fournies, après vérification du droit sur CHAQUE type. */
    private function filledDocs(): array
    {
        $docs = collect($this->docs)->filter(fn ($d) => ! empty($d['file']))->values()->all();

        foreach ($docs as $d) {
            $this->authorizeAction('create', EmployeeService::documentResource($d['type']));
        }

        return $docs;
    }

    // ---------- Enregistrement ----------

    public function save(EmployeeService $service)
    {
        return $this->employeeId ? $this->saveEdit($service) : $this->saveCreate($service);
    }

    public function saveAnyway(EmployeeService $service)
    {
        $this->confirmDuplicates = true;

        return $this->save($service);
    }

    private function saveCreate(EmployeeService $service)
    {
        $this->authorizeAction('create', 'employees');
        $cid = auth()->user()->company_id; // unique / exists ne passent PAS par le filtre automatique

        $validated = $this->validate($this->identityRules() + $this->docRules() + [
            'form.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'form.position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $cid)->where('department_id', $this->form['department_id'] ?: 0)],
            'form.hire_date' => ['required', 'date'],
            'form.contract_type' => ['required', Rule::in(['CDI', 'CDD', 'STAGE'])],
            'form.duration_months' => ['nullable', 'integer', 'min:1', 'max:60', 'required_unless:form.contract_type,CDI'],
            'form.base_salary' => ['required', 'integer', 'min:0'],
            'form.create_account' => ['boolean'],
            'form.login_email_source' => [Rule::in(['work', 'personal'])],
            'form.send_work' => ['boolean'],
            'form.send_personal' => ['boolean'],
        ]);

        $data = $validated['form'];
        $login = null;
        $sendTo = [];

        if (! empty($data['create_account'])) {
            $login = trim((string) ($data['login_email_source'] === 'work' ? ($data['work_email'] ?? '') : ($data['personal_email'] ?? '')));

            if ($login === '') {
                $this->addError('form.login_email_source', "Renseignez l'adresse choisie pour la connexion (professionnelle ou personnelle).");

                return;
            }

            $check = Validator::make(['email' => $login], ['email' => ['required', 'email', Rule::unique('users', 'email')]], [], ['email' => 'email du compte']);

            if ($check->fails()) {
                $this->addError('form.login_email_source', $check->errors()->first());

                return;
            }

            if (! empty($data['send_work']) && ! empty($data['work_email'])) {
                $sendTo[] = $data['work_email'];
            }
            if (! empty($data['send_personal']) && ! empty($data['personal_email'])) {
                $sendTo[] = $data['personal_email'];
            }
        }

        $documents = $this->filledDocs();

        if (! $this->confirmDuplicates && ($found = $this->findDuplicates($data))) {
            $this->duplicates = $found;

            return;
        }

        try {
            $result = $service->createMany([$data + ['login_email' => $login, 'send_to' => array_values(array_unique($sendTo)), 'documents' => $documents]]);
        } catch (UniqueConstraintViolationException $e) {
            report($e); // la cause exacte est écrite dans storage/logs/laravel.log

            $cause = str_contains($e->getMessage(), 'users_email') ? 'Un email de compte existe déjà.'
                : (str_contains($e->getMessage(), 'matricule') ? 'Ce matricule existe déjà.' : 'Une valeur qui doit être unique existe déjà.');

            $this->addError('save', "Enregistrement annulé : {$cause} Rien n'a été créé.");

            return;
        }

        $employee = $result['employees'][0];

        if ($result['credentials']) {
            $this->mailStatus = $service->sendCredentials($result['credentials']); // après la transaction
            $this->credentials = $result['credentials'];
            $this->createdId = $employee->id;
            $this->created = true;

            return;
        }

        session()->flash('notify', ['type' => 'success', 'message' => "Employé {$employee->matricule} enregistré."]);

        return $this->redirectRoute('employees.show', ['employee' => $employee->id], navigate: true);
    }

    private function saveEdit(EmployeeService $service)
    {
        $employee = Employee::findOrFail($this->employeeId);
        $this->authorizeAction('update', 'employees', $employee);
        $cid = auth()->user()->company_id;

        $validated = $this->validate($this->identityRules() + $this->docRules() + [
            'form.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'form.position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $cid)->where('department_id', $this->form['department_id'] ?: 0)],
            'form.manager_id' => ['nullable', 'integer', Rule::notIn([$employee->id]),
                Rule::exists('employees', 'id')->where('company_id', $cid)->whereNull('deleted_at')],
            'form.hire_date' => ['required', 'date'],
        ]);

        $service->update($employee, $validated['form'] + ['documents' => $this->filledDocs()]);

        session()->flash('notify', ['type' => 'success', 'message' => 'Employé mis à jour.']);

        return $this->redirectRoute('employees.show', ['employee' => $employee->id], navigate: true);
    }

    private function findDuplicates(array $data): array
    {
        if (empty($data['birth_date'])) {
            return [];
        }

        $existing = Employee::where('first_name', trim($data['first_name']))->where('last_name', trim($data['last_name']))
            ->whereDate('birth_date', $data['birth_date'])->first(['matricule']);

        return $existing
            ? ["« {$data['first_name']} {$data['last_name']} » ressemble à l'employé {$existing->matricule} (même nom et même date de naissance)."]
            : [];
    }

    public function deleteDocument(int $id): void
    {
        abort_unless($this->employeeId, 404);

        $doc = Document::where('employee_id', $this->employeeId)->findOrFail($id);
        $this->authorizeAction('delete', EmployeeService::documentResource($doc->type), $doc);

        $path = $doc->file_path;
        $doc->delete();
        Storage::disk('local')->delete($path);
        AuditLogger::log('deleted', 'documents', $id, ['type' => $doc->type, 'title' => $doc->title], null);

        $this->dispatch('notify', type: 'success', message: 'Pièce supprimée.');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.employees.employee-form', [
            'existingDocs' => $this->employeeId
                ? Document::where('employee_id', $this->employeeId)->latest('id')->get()
                    ->filter(fn ($d) => $user->canAccessRecord('read', EmployeeService::documentResource($d->type), $d))
                : collect(),
        ])->title($this->employeeId ? "Modifier l'employé" : 'Nouvel employé');
    }
}
