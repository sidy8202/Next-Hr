<div class="relative" x-data="{ open: @entangle('open') }" @click.outside="open = false">
    @if ($this->selected)
        <div class="flex items-center justify-between rounded-xl border border-line bg-surface px-3 py-2 text-sm">
            <span class="font-medium text-ink">
                <span class="font-mono font-bold text-brand-600 mr-2">{{ $this->selected->matricule }}</span>
                {{ $this->selected->full_name }}
            </span>
            <button type="button" wire:click="clear" class="text-ink-muted hover:text-rose-600 text-xs font-semibold">
                Changer
            </button>
        </div>
    @else
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" @focus="open = true"
                   placeholder="Rechercher par matricule ou nom..."
                   class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>

        @if ($open && strlen($search) >= 2)
            <div class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-line bg-surface p-1 shadow-xl">
                @forelse ($this->results as $emp)
                    <button type="button" wire:click="choose({{ $emp->id }})"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-surface-muted transition">
                        <span class="font-medium text-ink">{{ $emp->first_name }} {{ $emp->last_name }}</span>
                        <span class="font-mono text-xs font-bold text-brand-600">{{ $emp->matricule }}</span>
                    </button>
                @empty
                    <div class="p-3 text-center text-xs text-ink-muted">Aucun employé actif trouvé.</div>
                @endforelse
            </div>
        @endif
    @endif
</div>
