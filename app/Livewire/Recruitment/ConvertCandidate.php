<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Application;
use App\Models\Department;
use App\Models\Position;
use App\Services\CandidateConversionService;
use App\Services\EmployeeService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Créer la fiche employé')]
class ConvertCandidate extends Component
{
    use AuthorizesResource;

    public Application $application;

    public ?string $blocked = null;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public string $gender = '';
    public string $birth_date = '';
    public string $nationality = '';
    public string $national_id = '';
    public string $marital_status = 'single';
    public string $phone = '';
    public string $work_email = '';

    public $department_id = '';
    public $position_id = '';
    public string $hire_date = '';
    public string $contract_type = 'CDI';
    public $duration_months = '';
    public $base_salary = '';

    public bool $create_account = true;
    public string $login_email = '';

    public function mount(Application $application): void
    {
        $this->authorizeAction('create', 'employees');
        $this->application = $application->load(['candidate', 'jobOffer']);

        $candidate = $application->candidate;
        $offer = $application->jobOffer;

        if ($application->stage !== 'hired') {
            $this->blocked = 'Seule une candidature au stade « Embauché » peut devenir un employé.';
        } elseif ($candidate?->employee_id) {
            $this->blocked = 'Ce candidat a déjà une fiche employé.';
        }

        $this->phone = (string) ($candidate?->phone ?? '');
        $this->login_email = (string) ($candidate?->email ?? '');
        $this->department_id = $offer?->department_id ?? '';
        $this->position_id = $offer?->position_id ?? '';
        $this->contract_type = $offer?->contract_type ?? 'CDI';
        $this->hire_date = now()->toDateString();
    }

    public function updatedDepartmentId(): void
    {
        $this->position_id = '';
        unset($this->positions);
    }

    #[Computed]
    public function departments()
    {
        return Department::get()->sortBy(fn ($d) => $d->name ?? '')->values(); // filtré par entreprise
    }

    #[Computed]
    public function positions()
    {
        if (! $this->department_id) {
            return collect();
        }

        return Position::where('department_id', (int) $this->department_id)->get()
            ->sortBy(fn ($p) => $p->name ?? $p->title ?? '')->values();
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['required', 'date', 'before:today', 'after:1940-01-01'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'work_email' => ['nullable', 'email', 'max:190'],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('department_id', (int) $this->department_id)],
            'hire_date' => ['required', 'date'],
            'contract_type' => ['required', Rule::in(array_keys(config('recruitment.contract_types')))],
            'duration_months' => [Rule::requiredIf($this->contract_type !== 'CDI'), 'nullable', 'integer', 'between:1,120'],
            'base_salary' => ['required', 'integer', 'min:0'],
            'create_account' => ['boolean'],
            'login_email' => [Rule::requiredIf($this->create_account), 'nullable', 'email', 'max:190', Rule::unique('users', 'email')],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'gender' => 'genre', 'birth_date' => 'date de naissance', 'marital_status' => 'situation matrimoniale',
            'department_id' => 'département', 'position_id' => 'poste', 'hire_date' => 'date d’embauche',
            'contract_type' => 'type de contrat', 'duration_months' => 'durée du contrat (mois)',
            'base_salary' => 'salaire de base', 'login_email' => 'email de connexion', 'work_email' => 'email professionnel',
        ];
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'employees');

        if ($this->blocked) {
            return;
        }

        $this->validate();

        $candidate = $this->application->candidate;
        $departmentId = (int) $this->department_id;

        $person = [
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'nationality' => $this->nationality,
            'national_id' => $this->national_id,
            'marital_status' => $this->marital_status,
            'phone' => $this->phone,
            'work_email' => $this->work_email,
            'personal_email' => $candidate->email,
            'department_id' => $departmentId,
            'department_ids' => [$departmentId],           // ignoré si le service n'est pas multi-départements
            'primary_department_id' => $departmentId,
            'position_id' => (int) $this->position_id,
            'hire_date' => $this->hire_date,
            'contract_type' => $this->contract_type,
            'duration_months' => $this->contract_type === 'CDI' ? null : (int) $this->duration_months,
            'base_salary' => (int) $this->base_salary,
            'create_account' => $this->create_account,
            'login_email' => $this->login_email,
            'send_to' => array_values(array_unique(array_filter([mb_strtolower($this->login_email), mb_strtolower((string) $candidate->email)]))),
        ];

        $out = app(CandidateConversionService::class)->convert($this->application, $person);

        $credential = $out['credentials'][0] ?? null;
        $mail = null;

        if ($credential) {
            $status = app(EmployeeService::class)->sendCredentials($out['credentials']);
            $mail = $status[$credential['matricule']] ?? 'none';
        }

        $this->result = [
            'matricule' => $out['employee']->matricule,
            'name' => $out['employee']->full_name,
            'login' => $credential['login_email'] ?? null,
            'mail' => $mail,
            // Mot de passe provisoire affiché UNE SEULE FOIS, seulement si l'email n'est pas parti.
            'password' => ($credential && $mail !== 'sent') ? $credential['password'] : null,
        ];
    }

    public function render()
    {
        return view('livewire.recruitment.convert-candidate');
    }
}
