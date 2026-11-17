<div class="space-y-8">
    @foreach ($cards as $family)
        <section wire:key="fam-{{ $loop->index }}">
            <h3 class="mb-3 text-sm font-semibold text-ink">{{ $family['label'] }}</h3>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($family['items'] as $f)
                    <article wire:key="card-{{ $f['key'] }}" class="flex flex-col rounded-2xl border border-line bg-surface p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="text-sm font-semibold text-ink">{{ $f['title'] }}</h4>
                            @if ($f['status'] === 'available')
                                <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">Disponible</span>
                            @else
                                <span class="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-medium text-ink-muted">Lot {{ $f['lot'] }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-ink">{{ $f['objective'] }}</p>

                        <dl class="mt-3 space-y-1 text-xs text-ink-muted">
                            <div><dt class="inline font-medium">Données :</dt> <dd class="inline">{{ $f['dataset'] }}</dd></div>
                            <div><dt class="inline font-medium">Filtres :</dt> <dd class="inline">{{ implode(', ', $f['filters']) }}</dd></div>
                            @if ($f['reading'])<div><dt class="inline font-medium">Lecture :</dt> <dd class="inline">{{ $f['reading'] }}</dd></div>@endif
                            @if ($f['needs_history_months'])<div class="text-amber-700 dark:text-amber-300">Lecture saisonnière réservée aux historiques d’au moins {{ $f['needs_history_months'] }} mois.</div>@endif
                        </dl>

                        @if ($f['status'] === 'soon')
                            <p class="mt-3 rounded-lg bg-surface-muted px-3 py-2 text-xs text-ink-muted">Exemple de phrase (illustratif) : <span class="italic">{{ $f['example'] }}</span></p>
                        @endif

                        <div class="mt-4 flex-1"></div>
                        @if ($f['status'] === 'available')
                            <button type="button" wire:click="openFiche('{{ $f['key'] }}')" class="w-fit rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Ouvrir</button>
                        @else
                            <p class="text-xs text-ink-muted">Prévu au lot {{ $f['lot'] }}.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
