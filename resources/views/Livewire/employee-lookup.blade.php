<div class="relative" x-data @click.outside="$wire.open = false">
    @if ($this->selected)
        <div class="flex items-center justify-between gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-sm">
            <span><span class="font-semibold">{{ $this->selected->matricule }}</span> · {{ $this->selected->full_name }}</span>
            <button type="button" wire:click="clear" class="rounded p-1 text-ink-muted hover:text-ink" aria-label="Retirer la sélection">
                <x-heroicon-o-x-mark class="size-4" />
            </button>
        </div>
    @else
        <input type="text" wire:model.live.debounce.300ms="search" @focus="$wire.open = true"
               class="input" placeholder="{{ $placeholder }}" autocomplete="off" aria-label="Rechercher un employé">

        @if (! $this->canSearch)
            <p class="mt-1 text-xs text-amber-600">Vous n'avez pas le droit de consulter les employés.</p>
        @elseif ($open && strlen(trim($search)) >= 2)
            <ul class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-line bg-surface shadow-lg">
                @forelse ($this->results as $employee)
                    <li wire:key="lookup-{{ $target }}-{{ $employee->id }}">
                        <button type="button" wire:click="choose({{ $employee->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-surface-muted">
                            <span class="font-semibold">{{ $employee->matricule }}</span>
                            <span class="text-ink-muted">{{ $employee->full_name }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-3 py-2 text-sm text-ink-muted">Aucun employé trouvé.</li>
                @endforelse
            </ul>
        @endif
    @endif

    @if ($allowCreate && auth()->user()->canDo('create', 'employees'))
        <a href="{{ route('employees.create') }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm font-medium text-brand-600 hover:underline dark:text-brand-300">+ Nouvel employé (nouvel onglet)</a>
    @endif
</div>
