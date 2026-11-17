<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\PayProfile;
use App\Models\Position;
use App\Models\SalaryStep;
use App\Services\AuditLogger;
use App\Services\EmployeeService;
use App\Services\Payroll\EmployeeSalaryDraft;
use App\Services\Payroll\HypotheticalPay;
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
 * Flux : vue → save() → validation → EmployeeService (transaction : employé, contrat, ligne de salaire datée, modèle de traitement) → emails → redirection.
 *
 * Salaire à la création : si le poste a une catégorie salariale, on choisit un échelon (montant libre réservé à ceux qui peuvent modifier les salaires) ;
 * sinon montant libre. Net visé et modèle de traitement : facultatifs, réservés aux droits concernés. L'aperçu de paie est un confort : il ne bloque jamais l'enregistrement.
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
            'salary_step_id' => '', 'net_target' => '', 'net_mode' => 'reference', 'pay_profile_id' => '',
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
        $this->form['salary_step_id'] = '';
    }

    /** Un autre poste = une autre catégorie : l'échelon choisi n'a plus de sens. */
    public function updatedFormPositionId(): void
    {
        $this->form['salary_step_id'] = '';
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

    /** Le poste choisi (avec sa catégorie salariale éventuelle). */
    #[Computed]
    public function position(): ?Position
    {
        $id = $this->form['position_id'] ?? null;

        return $id ? Position::where('department_id', $this->form['department_id'] ?? 0)->find((int) $id) : null;
    }

    /** Échelons de la catégorie du poste choisi. */
    #[Computed]
    public function steps()
    {
        $gradeId = $this->position?->salary_grade_id;

        return $gradeId
            ? SalaryStep::whereHas('grade.grid')->where('salary_grade_id', $gradeId)->orderBy('sort')->orderBy('id')->get(['id', 'label', 'amount', 'salary_grade_id'])
            : collect();
    }

    /** Modèles de traitement proposés à la création (seulement à qui peut affecter un modèle). */
    #[Computed]
    public function profiles()
    {
        return $this->canAssignProfile ? PayProfile::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();
    }

    /** Montant libre hors échelon et net visé : réservés à ceux qui peuvent modifier les salaires. */
    #[Computed]
    public function canOverrideSalary(): bool
    {
        return auth()->user()->canDo('update', 'employee_salaries');
    }

    #[Computed]
    public function canSetNet(): bool
    {
        return auth()->user()->canDo('update', 'employee_salaries');
    }

    #[Computed]
    public function canAssignProfile(): bool
    {
        return auth()->user()->canDo('update', 'pay_profiles');
    }

    /** L'aperçu et le salaire en vigueur ne s'affichent qu'à ceux qui peuvent lire les salaires. */
    #[Computed]
    public function canSeeSalary(): bool
    {
        return auth()->user()->canDo('read', 'employee_salaries');
    }

    /** Salaire en vigueur d'un employé existant (lecture seule : un salaire se modifie dans « Employés et salaires »). */
    #[Computed]
    public function currentSalary(): ?EmployeeSalary
    {
        return $this->employeeId && $this->canSeeSalary
            ? EmployeeSalary::where('employee_id', $this->employeeId)->inForceOn(now()->toDateString())->with(['grade', 'step'])->orderByDesc('effective_from')->first()
            : null;
    }

    /**
     * Aperçu de la paie en direct (création seulement). Au barème : base, famille et modèle choisi, mois complet.
     * Il ne doit JAMAIS empêcher d'enregistrer : toute erreur devient un message.
     *
     * @return ?array<string, mixed>
     */
    #[Computed]
    public function preview(): ?array
    {
        if ($this->employeeId || ! $this->canSeeSalary) {
            return null;
        }

        $stepRaw = (string) ($this->form['salary_step_id'] ?? '');
        $hasGrade = (bool) $this->position?->salary_grade_id;
        $base = 0;

        if ($hasGrade && ctype_digit($stepRaw)) {
            $base = (int) ($this->steps->firstWhere('id', (int) $stepRaw)?->amount ?? 0);
        } elseif (! $hasGrade || $stepRaw === 'free') {
            $base = (int) preg_replace('/\D/', '', (string) ($this->form['base_salary'] ?? ''));
        }

        if ($base <= 0) {
            return ['state' => 'empty'];
        }

        $net = $this->canSetNet ? (int) preg_replace('/\D/', '', (string) ($this->form['net_target'] ?? '')) : 0;
        $guarantee = $net > 0 && ($this->form['net_mode'] ?? 'reference') === 'guarantee';
        $profile = $this->canAssignProfile && ! empty($this->form['pay_profile_id']) ? (int) $this->form['pay_profile_id'] : null;

        try {
            $r = HypotheticalPay::preview(auth()->user()->company_id, [
                'base' => $base,
                'marital_status' => ($this->form['marital_status'] ?? '') ?: null,
                'children' => (int) ($this->form['children_count'] ?? 0),
                'hire_date' => ($this->form['hire_date'] ?? '') ?: null,
                'pay_profile_id' => $profile,
                'guaranteed_net' => $guarantee ? $net : null,
            ]);
        } catch (\RuntimeException $e) {
            return ['state' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            report($e);

            return ['state' => 'error', 'message' => "L'aperçu n'a pas pu être calculé. L'enregistrement de l'employé reste possible."];
        }

        return ['state' => 'ok', 'guaranteed' => $guarantee, 'profile' => $profile !== null, 'reference' => $net > 0 && ! $guarantee ? ['net' => $net, 'gap' => $r['net'] - $net] : null] + $r;
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
            'form.salary_step_id' => 'échelon', 'form.net_target' => 'net visé', 'form.net_mode' => 'usage du net', 'form.pay_profile_id' => 'modèle de traitement',
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

        // Le salaire dépend de la catégorie du poste : montant libre seulement sans catégorie, ou si « Montant libre » est choisi.
        $position = Position::where('company_id', $cid)->find((int) ($this->form['position_id'] ?? 0));
        $gradeId = $position?->salary_grade_id;
        $stepRaw = (string) ($this->form['salary_step_id'] ?? '');
        $freeSalary = ! $gradeId || $stepRaw === 'free';

        $validated = $this->validate($this->identityRules() + $this->docRules() + [
            'form.department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $cid)],
            'form.position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $cid)->where('department_id', $this->form['department_id'] ?: 0)],
            'form.hire_date' => ['required', 'date'],
            'form.contract_type' => ['required', Rule::in(['CDI', 'CDD', 'STAGE'])],
            'form.duration_months' => ['nullable', 'integer', 'min:1', 'max:60', 'required_unless:form.contract_type,CDI'],
            'form.base_salary' => $freeSalary ? ['required', 'integer', 'min:0'] : ['nullable'],
            'form.salary_step_id' => ['nullable', 'string', 'max:20'],
            'form.net_target' => ['nullable', 'integer', 'min:0'],
            'form.net_mode' => ['nullable', Rule::in(['guarantee', 'reference'])],
            'form.pay_profile_id' => ['nullable', Rule::exists('pay_profiles', 'id')->where('company_id', $cid)->where('is_active', true)],
            'form.create_account' => ['boolean'],
            'form.login_email_source' => [Rule::in(['work', 'personal'])],
            'form.send_work' => ['boolean'],
            'form.send_personal' => ['boolean'],
        ]);

        // ▼▼ AJOUTE CE BLOC ICI cest ici jai collé▼▼
        if (! $this->employeeId && ! \App\Services\PlanGate::canAddEmployees(auth()->user()->company_id, 1)) {
            $this->addError('employee', 'Le plafond d’effectif de votre formule est atteint. Contactez-nous pour changer de formule.');
            return;
        }

        $data = $validated['form'];

        // Ligne de salaire à créer avec l'employé (règles dans EmployeeSalaryDraft).
        $step = null;

        if ($gradeId && ctype_digit($stepRaw)) {
            $model = SalaryStep::whereHas('grade.grid')->where('salary_grade_id', $gradeId)->find((int) $stepRaw);

            if (! $model) {
                $this->addError('form.salary_step_id', "Cet échelon n'appartient pas à la catégorie du poste.");

                return;
            }

            $step = ['id' => (int) $model->id, 'grade_id' => (int) $model->salary_grade_id, 'amount' => (int) $model->amount];
        }

        $salary = EmployeeSalaryDraft::resolve([
            'position_has_grade' => (bool) $gradeId,
            'step' => $step,
            'free_base' => $freeSalary ? (int) ($data['base_salary'] ?? 0) : 0,
            'can_override' => $this->canOverrideSalary,
            'net' => ($data['net_target'] ?? '') !== '' && $data['net_target'] !== null ? (int) $data['net_target'] : null,
            'net_mode' => $data['net_mode'] ?? 'reference',
            'can_set_net' => $this->canSetNet,
        ]);

        if (! $salary['ok']) {
            $this->addError($salary['field'] ?? 'form.base_salary', $salary['error']);

            return;
        }

        $data['salary'] = $salary;
        $data['base_salary'] = $salary['base']; // le contrat reçoit le même montant que la ligne de salaire
        $data['pay_profile_id'] = $this->canAssignProfile && ($data['pay_profile_id'] ?? '') !== '' && $data['pay_profile_id'] !== null ? (int) $data['pay_profile_id'] : null;
        unset($data['salary_step_id'], $data['net_target'], $data['net_mode']);

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
