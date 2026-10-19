<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmployeeService;
use App\Services\PresenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Fiche employé')]
class EmployeeShow extends Component
{
    use AuthorizesResource;

    public Employee $employee;

    #[Url(as: 'onglet')]
    public string $tab = 'info';

    public string $accountEmail = '';

    public bool $sendByEmail = true;

    /** Mot de passe provisoire : affiché une seule fois. */
    public ?string $tempPassword = null;

    public ?string $mailResult = null;

    public function mount(Employee $employee): void
    {
        $this->authorizeAction('read', 'employees', $employee);
        $this->employee = $employee;
        $this->accountEmail = (string) ($employee->work_email ?: $employee->personal_email);
    }

    public function createAccount(EmployeeService $service): void
    {
        $this->authorizeAction('create', 'users');
        $this->authorizeAction('update', 'employees', $this->employee);

        $data = $this->validate([
            'accountEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ], [], ['accountEmail' => 'email du compte']);

        abort_if(User::where('employee_id', $this->employee->id)->exists(), 409);

        $plain = Str::password(12, symbols: false);
        $login = Str::lower(trim($data['accountEmail']));

        DB::transaction(function () use ($login, $plain) {
            $user = User::forceCreate([
                'company_id' => $this->employee->company_id, 'employee_id' => $this->employee->id,
                'name' => $this->employee->full_name, 'email' => $login, 'password' => $plain,
                'is_active' => true, 'must_change_password' => true,
            ]);

            AuditLogger::log('account_created', 'users', $user->id, null, ['employee_id' => $this->employee->id, 'email' => $login]);
        });

        $this->mailResult = null;

        if ($this->sendByEmail) {
            $status = $service->sendCredentials([[
                'matricule' => $this->employee->matricule, 'name' => $this->employee->full_name,
                'login_email' => $login, 'password' => $plain, 'send_to' => [$login],
            ]]);
            $this->mailResult = $status[$this->employee->matricule] ?? 'none';
        }

        $this->tempPassword = $plain;
        $this->dispatch('notify', type: 'success', message: 'Compte créé.');
    }

    public function dismissPassword(): void
    {
        $this->tempPassword = null;
        $this->mailResult = null;
    }

    public function render()
    {
        $user = auth()->user();
        $canSeeContracts = $user->canDo('read', 'contracts');

        return view('livewire.employees.employee-show', [
            'employee' => $this->employee->load(['department:id,code,name', 'position:id,title']),
            'manager' => $this->employee->manager_id ? Employee::find($this->employee->manager_id) : null,
            'status' => app(PresenceService::class)->statusesFor([$this->employee->id]),
            'canSeeContracts' => $canSeeContracts,
            'contracts' => ($this->tab === 'contracts' && $canSeeContracts)
                ? Contract::where('employee_id', $this->employee->id)->orderByDesc('start_date')->get() : collect(),
            'documents' => $this->tab === 'documents'
                ? Document::where('employee_id', $this->employee->id)->latest('id')->get()
                    ->filter(fn ($d) => $user->canAccessRecord('read', EmployeeService::documentResource($d->type), $d))
                : collect(),
            'account' => $this->tab === 'account'
                ? User::where('employee_id', $this->employee->id)->first(['id', 'email', 'is_active', 'must_change_password', 'first_login_at'])
                : null,
        ]);
    }
}
