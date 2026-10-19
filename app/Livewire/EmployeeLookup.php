<?php

namespace App\Livewire;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Sélecteur d'employé par code (matricule) ou par nom, avec création rapide.
 * Usage : <livewire:employee-lookup wire:model="form.head_id" target="head" wire:key="lookup-head" />
 * Le « target » identifie ce sélecteur quand plusieurs sont présents sur la même page.
 */
class EmployeeLookup extends Component
{
    #[Modelable]
    public ?int $value = null;

    public string $target = '';

    public string $placeholder = 'Code ou nom de l’employé…';

    public bool $allowCreate = true;

    public string $search = '';

    public bool $open = false;

    public function updatedSearch(): void
    {
        $this->open = true;
    }

    public function choose(int $id): void
    {
        $this->value = $id;
        $this->search = '';
        $this->open = false;
    }

    public function clear(): void
    {
        $this->value = null;
    }

    public function createNew(): void
    {
        $this->dispatch('open-employee-form', mode: 'single', target: $this->target ?: 'lookup');
    }

    #[On('employee-created')]
    public function onEmployeeCreated(int $id, string $target = ''): void
    {
        if ($target !== '' && $target === $this->target) {
            $this->choose($id);
        }
    }

    #[Computed]
    public function canSearch(): bool
    {
        return auth()->user()->canDo('read', 'employees');
    }

    #[Computed]
    public function selected(): ?Employee
    {
        return $this->value ? Employee::visibleTo(auth()->user())->find($this->value) : null;
    }

    #[Computed]
    public function results(): Collection
    {
        $term = trim($this->search);

        if (! $this->canSearch || strlen($term) < 2) {
            return collect();
        }

        return Employee::visibleTo(auth()->user())
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('matricule', 'like', $term.'%')
                ->orWhere('first_name', 'like', $term.'%')
                ->orWhere('last_name', 'like', $term.'%'))
            ->orderBy('matricule')
            ->limit(8)
            ->get(['id', 'matricule', 'first_name', 'last_name']);
    }

    public function render()
    {
        return view('livewire.employee-lookup');
    }
}
