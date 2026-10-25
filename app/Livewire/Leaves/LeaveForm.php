<?php

namespace App\Livewire\Leaves;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Demande de congé')]
class LeaveForm extends Component
{
    use AuthorizesResource, WithFileUploads;

    /** Réservé à la RH : déposer une demande au nom d'un employé. */
    public $employee_id = '';

    public $leave_type_id = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $start_half = false;
    public bool $end_half = false;
    public string $reason = '';
    public string $exception_reason = '';
    public $interim_employee_id = '';
    public $attachment = null;

    public function mount(): void
    {
        $this->authorizeAction('create', 'leave_requests');
        LeaveType::ensureDefaults(auth()->user()->company_id);

        $first = $this->types->first();
        $this->leave_type_id = $first?->id ?? '';
    }

    #[Computed]
    public function asHr(): bool
    {
        return \App\Support\LeaveVisibility::isHr(auth()->user(), 'leave_requests');
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
        return LeaveType::where('active', true)->orderBy('sort')->get();
    }

    #[Computed]
    public function selectedType(): ?LeaveType
    {
        return $this->types->firstWhere('id', (int) $this->leave_type_id);
    }

    #[Computed]
    public function employees()
    {
        return $this->asHr ? Employee::where('status', 'active')->orderBy('last_name')->get(['id', 'matricule', 'first_name', 'last_name']) : collect();
    }

    #[Computed]
    public function colleagues()
    {
        return $this->target
            ? Employee::where('status', 'active')->where('id', '!=', $this->target->id)->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
            : collect();
    }

    /** Aperçu en direct : nombre de jours, solde, exception ou refus. */
    #[Computed]
    public function preview(): array
    {
        if (! $this->target) {
            return ['days' => 0.0, 'errors' => [], 'available' => null, 'after' => null, 'exception' => false, 'tolerance' => 0.0, 'pool' => null];
        }

        return app(LeaveService::class)->evaluate(
            $this->target, $this->selectedType, $this->start_date ?: null, $this->end_date ?: null,
            $this->start_half, $this->end_half, $this->asHr
        );
    }

    public function updatedStartDate(): void
    {
        if ($this->start_date && (! $this->end_date || $this->end_date < $this->start_date)) {
            $this->end_date = $this->start_date; // un jour par défaut
        }
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $companyId)->where('active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'exception_reason' => ['nullable', 'string', 'max:1000'],
            'interim_employee_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'attachment' => ['nullable', 'file', 'mimes:'.implode(',', config('leaves.attachment.mimes')), 'max:'.config('leaves.attachment.max_kb')],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'leave_type_id' => 'type de congé', 'start_date' => 'date de début', 'end_date' => 'date de fin',
            'attachment' => 'justificatif', 'interim_employee_id' => 'intérimaire', 'exception_reason' => 'motif de l’exception',
        ];
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'leave_requests');
        $this->validate();

        abort_unless($this->target, 422, 'Aucune fiche employé n’est rattachée à votre compte.');

        $leave = app(LeaveService::class)->submit($this->target, $this->only([
            'leave_type_id', 'start_date', 'end_date', 'start_half', 'end_half', 'reason', 'exception_reason', 'interim_employee_id',
        ]), $this->attachment, $this->asHr);

        session()->flash('status', 'Demande envoyée : elle suit la validation du chef de département puis des ressources humaines.');
        $this->redirectRoute('leaves.show', ['leave' => $leave->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.leaves.leave-form');
    }
}
