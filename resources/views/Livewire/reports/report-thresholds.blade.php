<section class="no-print rounded-2xl border border-line bg-surface p-5 shadow-sm" x-data="{ open: false }">
    <button type="button" @click="open = ! open" :aria-expanded="open" class="flex w-full items-center justify-between gap-2 text-left">
        <span>
            <span class="block text-sm font-semibold">Seuils de lecture</span>
            <span class="block text-xs text-ink-muted">Ils décident quand une phrase signale une variation. Chaque phrase affiche le seuil qu’elle a appliqué.</span>
        </span>
        <span class="text-xs text-ink-muted" x-text="open ? 'Masquer' : 'Afficher'"></span>
    </button>

    <div x-show="open" x-cloak class="mt-4 space-y-3">
        @foreach ($thresholdDefs as $key => $def)
            <div wire:key="thr-{{ $key }}" class="grid items-center gap-2 sm:grid-cols-3">
                <label class="text-sm text-ink sm:col-span-2" for="thr-{{ $key }}">{{ $def['label'] }} <span class="text-xs text-ink-muted">({{ $def['min'] }} à {{ $def['max'] }} {{ $def['unit'] }}, par défaut {{ $def['default'] }})</span></label>
                @if ($canEditThresholds)
                    <div>
                        <input id="thr-{{ $key }}" type="number" min="{{ $def['min'] }}" max="{{ $def['max'] }}" wire:model="thresholdInputs.{{ $key }}" class="input py-1.5 text-sm">
                        @error('thresholdInputs.'.$key)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <p class="text-sm font-medium text-ink">{{ $thresholds[$key] }} {{ $def['unit'] }}</p>
                @endif
            </div>
        @endforeach

        @if ($canEditThresholds)
            <button type="button" wire:click="saveThresholds" wire:loading.attr="disabled" wire:target="saveThresholds" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">Enregistrer les seuils</button>
        @else
            <p class="text-xs text-ink-muted">Seuls ceux qui peuvent modifier les paramètres de paie changent ces seuils.</p>
        @endif

        <p class="text-xs text-ink-muted">Un groupe de moins de {{ \App\Services\Reports\GridGapReport::MIN_GROUP }} personnes n’est jamais affiché seul : ce garde-fou de confidentialité n’est pas modifiable.</p>
    </div>
</section>
