@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
    $pctText = fn ($v) => $v === null ? '—' : $v.' %';
    $tabs = ['overview' => 'Vue d’ensemble', 'seasonality' => 'Saisonnalité', 'segments' => 'Types d’entreprises', 'retention' => 'Entonnoir et fidélisation', 'forecast' => 'Prévisions', 'comparison' => 'Comparaisons'];
    $palette = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9'];
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
            <h2 class="text-2xl font-bold">Rapports et analyses</h2>
            <p class="text-sm text-ink-muted">Du {{ $from_d->format('d/m/Y') }} au {{ $to_d->format('d/m/Y') }} · {{ $companiesCount }} entreprise(s) correspondent aux filtres. Chaque tableau et chaque graphique se recalcule avec vos critères.</p>
        </div>
        <div class="no-print flex flex-wrap gap-2">
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Exporter (Excel)</button>
            <button type="button" onclick="window.print()" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted">Imprimer / PDF</button>
        </div>
    </div>

    {{-- Filtres --}}
    <section class="no-print space-y-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Période</label>
                <select wire:model.live="preset" class="input py-1.5 text-sm">@foreach (\App\Services\ReportPeriod::PRESETS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            @if ($preset === 'custom')
                <div><label class="mb-1 block text-xs font-medium text-ink-muted">Du</label><input type="date" wire:model.live="from" class="input py-1.5 text-sm"></div>
                <div><label class="mb-1 block text-xs font-medium text-ink-muted">Au</label><input type="date" wire:model.live="to" class="input py-1.5 text-sm"></div>
            @endif
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Comparer à</label>
                <select wire:model.live="compare" class="input py-1.5 text-sm"><option value="none">Aucune comparaison</option><option value="previous">Période précédente</option><option value="year">Même période l’an dernier</option></select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Secteur</label>
                <select wire:model.live="sector" class="input py-1.5 text-sm"><option value="">Tous</option>@foreach (config('platform.sectors') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Pays</label>
                <select wire:model.live="country" class="input py-1.5 text-sm"><option value="">Tous</option>@foreach (config('platform.countries') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Formule</label>
                <select wire:model.live="plan" class="input py-1.5 text-sm"><option value="">Toutes</option>@foreach ($plans as $p)<option value="{{ $p->code }}">{{ $p->name }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Période de facturation</label>
                <select wire:model.live="period" class="input py-1.5 text-sm"><option value="">Toutes</option>@foreach (config('platform.periods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Taille (effectif)</label>
                <select wire:model.live="size" class="input py-1.5 text-sm"><option value="">Toutes</option>@foreach (['0-25', '26-55', '56-100', '101-250', '251+'] as $b)<option value="{{ $b }}">{{ $b }} employés</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Statut de l’entreprise</label>
                <select wire:model.live="status" class="input py-1.5 text-sm"><option value="">Tous</option><option value="trial">Essai</option><option value="active">Actif</option><option value="suspended">Suspendu</option></select></div>
            <div class="flex items-end"><button type="button" wire:click="resetFilters" class="w-full rounded-xl border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Réinitialiser</button></div>
        </div>
    </section>

    {{-- Onglets --}}
    <nav class="no-print flex flex-wrap gap-1 rounded-2xl border border-line bg-surface p-1 shadow-sm">
        @foreach ($tabs as $k => $l)
            <button type="button" wire:click="$set('tab', '{{ $k }}')" class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $tab === $k ? 'bg-indigo-600 text-white shadow' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}">{{ $l }}</button>
        @endforeach
    </nav>

    @if ($companiesCount === 0)
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Aucune entreprise ne correspond à ces filtres. Élargissez vos critères ou cliquez sur « Réinitialiser ».</p>
    @else
        @includeWhen($tab === 'overview', 'livewire.platform.report-overview')
        @includeWhen($tab === 'seasonality', 'livewire.platform.report-seasonality')
        @includeWhen($tab === 'segments', 'livewire.platform.report-segments')
        @includeWhen($tab === 'retention', 'livewire.platform.report-retention')
        @includeWhen($tab === 'forecast', 'livewire.platform.report-forecast')
        @includeWhen($tab === 'comparison', 'livewire.platform.report-comparison')
    @endif
</div>
