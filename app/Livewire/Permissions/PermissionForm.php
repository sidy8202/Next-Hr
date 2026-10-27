<?php

namespace App\Livewire\Permissions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\PermissionType;
use App\Services\PermissionService;
use App\Support\LeaveVisibility;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Demande de permission')]
class PermissionForm extends Component
{
    use AuthorizesResource, WithFileUploads;

    public $employee_id = '';
    public $permission_type_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $start_time = '';
    public string $end_time = '';
    public string $reason = '';
    public string $exception_reason = '';
    public $attachment = null;

    public function mount(): void
    {
        $this->authorizeAction('create', 'absence_permissions');
        PermissionType::ensureDefaults(auth()->user()->company_id);
        $this->permission_type_id = $this->types->first()?->id ?? '';
    }

    #[Computed]
    public function asHr(): bool
    {
        return LeaveVisibility::isHr(auth()->user(), 'absence_permissions');
    }

    #[Computed]
    public function target(): ?Employee
    {
        $id = ($this->asHr && $this->employee_id) ? (int) $this->employee_id : auth()->user()->employee_id;

        return $id ? Employee::find($id) : null;
    }

    #[Computed]
    public function types()
    {
        return PermissionType::where('active', true)->orderBy('sort')->get();
    }

    #[Computed]
    public function selectedType(): ?PermissionType
    {
        return $this->types->firstWhere('id', (int) $this->permission_type_id);
    }

    #[Computed]
    public function employees()
    {
        return $this->asHr ? Employee::where('status', 'active')->orderBy('last_name')->get(['id', 'matricule', 'first_name', 'last_name']) : collect();
    }

    /** Aperçu en direct : jours gratuits, jours retirés du congé, solde. */
    #[Computed]
    public function preview(): array
    {
        if (! $this->target) {
            return ['days' => 0.0, 'hours' => null, 'free' => 0.0, 'deducted' => 0.0, 'unpaid' => 0.0, 'errors' => [], 'available' => null, 'after' => null, 'exception' => false, 'tolerance' => 0.0];
        }

        return app(PermissionService::class)->evaluate($this->target, $this->selectedType, $this->only(['start_date', 'end_date', 'start_time', 'end_time']), $this->asHr);
    }

    public function updatedStartDate(): void
    {
        if ($this->start_date && (! $this->end_date || $this->end_date < $this->start_date)) {
            $this->end_date = $this->start_date;
        }
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;
        $hours = $this->selectedType?->kind === 'hours';

        return [
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'permission_type_id' => ['required', Rule::exists('permission_types', 'id')->where('company_id', $companyId)->where('active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => [$hours ? 'nullable' : 'required', 'date', 'after_or_equal:start_date'],
            'start_time' => [$hours ? 'required' : 'nullable', 'date_format:H:i'],
            'end_time' => [$hours ? 'required' : 'nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'exception_reason' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:'.implode(',', config('leaves.attachment.mimes')), 'max:'.config('leaves.attachment.max_kb')],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['permission_type_id' => 'type de permission', 'start_date' => 'date de début', 'end_date' => 'date de fin', 'start_time' => 'heure de début', 'end_time' => 'heure de fin', 'attachment' => 'justificatif'];
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'absence_permissions');
        $this->validate();
        abort_unless($this->target, 422, 'Aucune fiche employé n’est rattachée à votre compte.');

        $request = app(PermissionService::class)->submit($this->target, $this->only([
            'permission_type_id', 'start_date', 'end_date', 'start_time', 'end_time', 'reason', 'exception_reason',
        ]), $this->attachment, $this->asHr);

        session()->flash('status', 'Demande envoyée : elle suit la validation du chef de département puis des ressources humaines.');
        $this->redirectRoute('permissions.show', ['permission' => $request->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.permissions.permission-form');
    }
}
