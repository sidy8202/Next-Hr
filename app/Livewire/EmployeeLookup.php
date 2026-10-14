<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class EmployeeLookup extends Component
{
    #[Modelable]
    public ?int $value = null;

    public string $search = '';

    public bool $open = false;

    public function choose(int $id): void
    {
        $this->value = $id;
        $this->open = false;
        $this->search = '';
    }

    public function clear(): void
    {
        $this->value = null;
        $this->search = '';
    }

    #[Computed]
    public function selected(): ?Employee
    {
        return $this->value ? Employee::visibleTo(auth()->user())->find($this->value) : null;
    }

    #[Computed]
    public function results()
    {
        abort_unless(auth()->user()->canDo('read', 'employees'), 403);

        if (strlen($this->search) < 2) {
            return collect();
        }

        $term = $this->search;

        return Employee::visibleTo(auth()->user())
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('matricule', 'like', "{$term}%")
                ->orWhere('first_name', 'like', "{$term}%")
                ->orWhere('last_name', 'like', "{$term}%"))
            ->orderBy('matricule')
            ->limit(8)
            ->get(['id', 'matricule', 'first_name', 'last_name']);
    }

    public function render()
    {
        return view('livewire.employee-lookup');
    }
}
