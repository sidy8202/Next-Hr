<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Tableau côté serveur : recherche, tri, pagination et sélection, synchronisés dans l'URL.
 * Les propriétés #[Url] viennent du navigateur : ce sont des entrées utilisateur, jamais de confiance.
 */
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

    /** @var array<int, string> identifiants cochés (en texte, comme les cases à cocher) */
    public array $selected = [];

    /** Colonnes autorisées au tri : liste blanche. */
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
        if (in_array($name, ['search', 'perPage'], true) || str_starts_with($name, 'filters.')) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** Coche ou décoche toute la page affichée. */
    public function togglePage(array $ids): void
    {
        $ids = array_map('strval', $ids);
        $allChecked = $ids !== [] && count(array_diff($ids, $this->selected)) === 0;

        $this->selected = $allChecked
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));
    }

    /** @return array<int, int> */
    protected function selectedIds(): array
    {
        return array_map('intval', $this->selected);
    }

    protected function orderColumn(string $default): string
    {
        return in_array($this->sortBy, $this->sortableColumns(), true) ? $this->sortBy : $default;
    }

    protected function orderDirection(): string
    {
        return $this->sortDir === 'desc' ? 'desc' : 'asc';
    }

    protected function pageSize(): int
    {
        return in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
    }

    /** Neutralise l'injection de formules dans Excel (=, +, -, @ en début de cellule). */
    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
