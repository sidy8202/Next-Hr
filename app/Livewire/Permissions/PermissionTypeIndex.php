<?php

namespace App\Livewire\Permissions;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\PermissionType;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Types de permission et leurs règles (quota gratuit, règle pour l'excédent, plafonds, message de la fiche). */
#[Layout('components.layouts.app')]
#[Title('Types de permission')]
class PermissionTypeIndex extends Component
{
    use AuthorizesResource;

    public ?int $editing = null;
    public string $name = '';
    public string $kind = 'days';
    public $free_days = 2;
    public string $excess_policy = 'deduct_leave';
    public $max_days = '';
    public $yearly_cap_days = '';
    public $hours_free_per_month = '';
    public bool $requires_document = false;
    public string $sheet_message = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'leave_settings');
        PermissionType::ensureDefaults(auth()->user()->company_id);
    }

    #[Computed]
    public function types()
    {
        return PermissionType::orderBy('sort')->get();
    }

    public function edit(int $id): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $t = PermissionType::findOrFail($id);
        $this->editing = $t->id;
        $this->name = $t->name;
        $this->kind = $t->kind;
        $this->free_days = $t->free_days;
        $this->excess_policy = $t->excess_policy;
        $this->max_days = $t->max_days ?? '';
        $this->yearly_cap_days = $t->yearly_cap_days ?? '';
        $this->hours_free_per_month = $t->hours_free_per_month ?? '';
        $this->requires_document = $t->requires_document;
        $this->sheet_message = (string) $t->sheet_message;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editing', 'name', 'max_days', 'yearly_cap_days', 'hours_free_per_month', 'requires_document', 'sheet_message']);
        $this->kind = 'days';
        $this->free_days = 2;
        $this->excess_policy = 'deduct_leave';
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeAction('update', 'leave_settings');

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', 'in:days,hours'],
            'free_days' => ['required', 'numeric', 'between:0,60'],
            'excess_policy' => ['required', Rule::in(array_keys(PermissionType::POLICIES))],
            'max_days' => ['nullable', 'numeric', 'between:0.5,365'],
            'yearly_cap_days' => ['nullable', 'numeric', 'between:0.5,365'],
            'hours_free_per_month' => ['nullable', 'numeric', 'between:0,200'],
            'sheet_message' => ['nullable', 'string', 'max:500'],
        ], [], ['name' => 'nom', 'free_days' => 'jours gratuits', 'max_days' => 'durée maximale']);

        $data = [
            'name' => trim($this->name), 'kind' => $this->kind, 'free_days' => (float) $this->free_days,
            'excess_policy' => $this->excess_policy,
            'max_days' => $this->max_days !== '' ? (float) $this->max_days : null,
            'yearly_cap_days' => $this->yearly_cap_days !== '' ? (float) $this->yearly_cap_days : null,
            'hours_free_per_month' => $this->kind === 'hours' && $this->hours_free_per_month !== '' ? (float) $this->hours_free_per_month : null,
            'requires_document' => $this->requires_document,
            'sheet_message' => trim($this->sheet_message) ?: null,
        ];

        if ($this->editing) {
            $type = PermissionType::findOrFail($this->editing);
            $type->update($data);
            AuditLogger::log('updated', 'leave_settings', $type->id, null, $data);
        } else {
            $type = PermissionType::create($data + ['code' => 'p'.substr(md5($this->name.microtime()), 0, 8), 'active' => true, 'sort' => ($this->types->max('sort') ?? 0) + 1]);
            AuditLogger::log('created', 'leave_settings', $type->id, null, $data);
        }

        $this->cancelEdit();
        unset($this->types);
        $this->dispatch('notify', type: 'success', message: 'Type de permission enregistré.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAction('update', 'leave_settings');
        $type = PermissionType::findOrFail($id);
        $type->update(['active' => ! $type->active]);
        unset($this->types);
    }

    public function render()
    {
        return view('livewire.permissions.permission-type-index');
    }
}
