@props(['column', 'sortBy', 'sortDir'])

<th class="px-4 py-3" aria-sort="{{ $sortBy === $column ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <button type="button" wire:click="sort('{{ $column }}')" class="inline-flex items-center gap-1 font-semibold uppercase tracking-wide hover:text-ink">
        {{ $slot }}
        @if ($sortBy === $column)
            <x-dynamic-component :component="'heroicon-o-chevron-'.($sortDir === 'asc' ? 'up' : 'down')" class="size-3.5" />
        @endif
    </button>
</th>
