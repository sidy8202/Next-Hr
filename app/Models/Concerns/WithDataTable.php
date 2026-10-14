<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

trait WithDataTable
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tri')]
    public string $sortBy = '';

    #[Url(as: 'sens')]
    public string $sortDir = 'asc';

    #[Url(as: 'par')]
    public int $perPage = 10;

    /** @var array<int, int|string> */
    public array $selected = [];

    /** Colonnes autorisées au tri : liste blanche pour prévenir les injections SQL */
    abstract protected function sortableColumns(): array;

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        $this->sortDir = ($this->sortBy === $column && $this->sortDir === 'asc') ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function updated(string $name): void
    {
        if ($name === 'perPage') {
            $this->perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
        }

        if (in_array($name, ['search', 'perPage'], true) || str_starts_with($name, 'filters.')) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }
}
