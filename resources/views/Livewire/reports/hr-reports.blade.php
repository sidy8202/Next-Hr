@assets
<script>window.nexthrReady = window.nexthrReady || function (cb) { requestAnimationFrame(function () { cb(); }); };</script>
@endassets

@php
    $tabs = ['synthesis' => 'Synthèse', 'workforce' => 'Effectif'];
    $soon = ['Départs et rotation', 'Absences et congés', 'Temps de travail', 'Discipline', 'Missions', 'Recrutement', 'Rémunération'];
    $groups = [
        'depts' => ['Départements', $options['depts']],
        'positions' => ['Postes', $options['positions']],
        'contracts' => ['Types de contrat', collect($options['contracts'])->mapWithKeys(fn ($c) => [$c => $c])->all()],
        'genders' => ['Sexe', $options['genders']],
    ];
    $selected = ['depts' => $depts, 'positions' => $positions, 'contracts' => $contracts, 'genders' => $genders];
    $activeCount = collect($selected)->flatten()->count();
@endphp

<div class="space-y-6">
    <style>
        @media print {
            aside, header, .no-print, [x-cloak] { display: none !important; }
            main { overflow: visible !important; padding: 0 !important; }
            .h-screen { height: auto !important; overflow: visible !important; }
            body { background: #fff !important; color: #111 !important; }
            .print-keep { break-inside: avoid; }
        }
    </style>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Rapports RH</h2>
            <p class="text-sm text-ink-muted">Du {{ $from_d->format('d/m/Y') }} au {{ $to_d->format('d/m/Y') }}@if ($forced !== null) · vue limitée à votre département @endif. Chaque tableau et chaque graphique se recalcule avec vos critères.</p>
        </div>
        <div class="no-print flex flex-wrap gap-2">
            <button type="button" wire:click="refreshData" wire:loading.attr="disabled" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Actualiser</button>
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Exporter (Excel)</button>
            <button type="button" onclick="window.print()" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted">Imprimer / PDF</button>
        </div>
    </div>

    {{-- Filtres --}}
    <section class="no-print space-y-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Période</label>
                <select wire:model.live="preset" class="input py-1.5 text-sm">@foreach (\App\Services\ReportPeriod::PRESETS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            @if ($preset === 'custom')
                <div><label class="mb-1 block text-xs font-medium text-ink-muted">Du</label><input type="date" wire:model.live="from" class="input py-1.5 text-sm"></div>
                <div><label class="mb-1 block text-xs font-medium text-ink-muted">Au</label><input type="date" wire:model.live="to" class="input py-1.5 text-sm"></div>
            @endif
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Comparer à</label>
                <select wire:model.live="compare" class="input py-1.5 text-sm"><option value="none">Aucune comparaison</option><option value="previous">Période précédente</option><option value="year">Même période l’an dernier</option></select></div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($groups as $key => [$label, $items])
                @continue($key === 'depts' && $forced !== null)
                <details class="rounded-xl border border-line" wire:key="grp-{{ $key }}">
                    <summary class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm font-medium">
                        <span>{{ $label }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ count($selected[$key]) ? 'bg-indigo-600 text-white' : 'bg-surface-muted text-ink-muted' }}">{{ count($selected[$key]) ?: 'Tous' }}</span>
                    </summary>
                    <div class="max-h-48 space-y-1 overflow-y-auto border-t border-line p-3">
                        @if (count($selected[$key]))<button type="button" wire:click="clearGroup('{{ $key }}')" class="mb-1 text-xs text-indigo-600 hover:underline">Tout décocher</button>@endif
                        @forelse ($items as $id => $name)
                            <label class="flex items-center gap-2 text-sm" wire:key="opt-{{ $key }}-{{ $id }}"><input type="checkbox" wire:model.live="{{ $key }}" value="{{ $id }}" class="rounded"> {{ $name }}</label>
                        @empty
                            <p class="text-xs text-ink-muted">Aucune valeur.</p>
                        @endforelse
                    </div>
                </details>
            @endforeach
        </div>

        @if ($activeCount)
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($selected as $key => $values)
                    @foreach ($values as $v)
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-200" wire:key="chip-{{ $key }}-{{ $v }}">{{ $groups[$key][0] }} : {{ $groups[$key][1][$v] ?? $v }}<button type="button" wire:click="removeFilter('{{ $key }}', '{{ $v }}')" class="ml-1 font-bold hover:text-rose-600" aria-label="Retirer">×</button></span>
                    @endforeach
                @endforeach
                <button type="button" wire:click="resetFilters" class="text-xs text-ink-muted hover:underline">Tout réinitialiser</button>
            </div>
        @endif
    </section>

    {{-- Onglets --}}
    <nav class="no-print flex flex-wrap gap-1 rounded-2xl border border-line bg-surface p-1 shadow-sm">
        @foreach ($tabs as $k => $l)
            <button type="button" wire:click="$set('tab', '{{ $k }}')" class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $tab === $k ? 'bg-indigo-600 text-white shadow' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}">{{ $l }}</button>
        @endforeach
        @foreach ($soon as $l)
            <span class="cursor-not-allowed rounded-xl px-4 py-2 text-sm font-medium text-ink-muted/60" title="Arrive prochainement">{{ $l }} <span class="ml-1 rounded-full bg-surface-muted px-1.5 py-0.5 text-[10px] uppercase">bientôt</span></span>
        @endforeach
    </nav>

    @includeWhen($tab === 'synthesis', 'livewire.reports.hr-synthesis')
    @includeWhen($tab === 'workforce', 'livewire.reports.hr-workforce')
</div>
