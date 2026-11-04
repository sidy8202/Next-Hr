@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
    $k = $s['kpis'];
    $pct = fn ($a, $b) => $b > 0 ? (int) round(($a - $b) / $b * 100) : null;
    $mrrSeries = $s['revenue']['mrr'];
    $mrrDelta = count($mrrSeries) > 1 ? $pct(end($mrrSeries), $mrrSeries[count($mrrSeries) - 2]) : null;
    $dot = ['danger' => 'bg-rose-500', 'warning' => 'bg-amber-500', 'info' => 'bg-sky-500'];
    $metricLabels = ['companies' => 'Entreprises', 'employees' => 'Effectif', 'mrr' => 'Revenu mensuel'];
    $cell = fn ($v) => $pivotMetric === 'mrr' ? ($v ? number_format($v, 0, ',', ' ') : '·') : ($v ?: '·');
@endphp

<div class="space-y-6">
    <x-ui.reveal class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Bonjour {{ explode(' ', auth()->user()->name)[0] }}</h2>
            <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l d F Y') }} · données du {{ \Carbon\Carbon::parse($s['generated_at'])->format('H:i') }}</p>
        </div>
        <button type="button" wire:click="refreshStats" wire:loading.attr="disabled" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm hover:bg-surface-muted disabled:opacity-60">Actualiser</button>
    </x-ui.reveal>

    {{-- Indicateurs clés --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-platform.kpi label="Entreprises clientes" :value="$k['companies']" :spark="$k['spark_new']" color="#6366f1" :delay="0"
            :hint="$k['active'].' actives · '.$k['trial'].' en essai · '.$k['suspended'].' suspendues'" />
        <x-platform.kpi label="Effectif géré" :value="$k['employees']" suffix=" employés" color="#0ea5e9" :delay="70"
            :hint="$k['no_plan'].' entreprise(s) sans formule'" />
        <x-platform.kpi label="Revenu mensuel récurrent" :value="$k['mrr']" suffix=" FCFA" :spark="$mrrSeries" color="#10b981" :delta="$mrrDelta" hint="vs mois dernier" :delay="140" />
        <x-platform.kpi label="Encaissé ce mois" :value="$k['collected']" suffix=" FCFA" color="#8b5cf6" :delta="$pct($k['collected'], $k['collected_prev'])" hint="vs mois dernier" :delay="210" />
        <x-platform.kpi label="À traiter" :value="count($s['alerts'])" color="#f43f5e" :delay="280" hint="échéances, retards, plafonds" />
    </div>

    {{-- Revenus + répartition par formule --}}
    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="100" class="rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Revenus sur 12 mois</h3>
            <p class="mb-3 text-xs text-ink-muted">Encaissements réels et revenu mensuel récurrent estimé.</p>
            <x-charts.area :labels="$s['revenue']['labels']" :series="[
                ['name' => 'Revenu mensuel récurrent', 'color' => '#10b981', 'values' => $s['revenue']['mrr'], 'fill' => true],
                ['name' => 'Encaissé', 'color' => '#6366f1', 'values' => $s['revenue']['collected'], 'fill' => false],
            ]" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Entreprises par formule</h3>
            <p class="mb-4 text-xs text-ink-muted">Abonnements en cours d’utilisation.</p>
            <x-charts.donut :items="$s['distribution']" :center="$k['companies']" center-label="entreprises" />
        </x-ui.reveal>
    </div>

    {{-- Croissance + secteurs --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.reveal :delay="120" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Croissance nette</h3>
            <p class="mb-3 text-xs text-ink-muted">Nouvelles entreprises (haut) et abonnements perdus (bas), par mois.</p>
            <x-charts.columns :labels="$s['growth']['labels']" :up="$s['growth']['new']" :down="$s['growth']['lost']" up-label="Nouvelles entreprises" down-label="Abonnements perdus" />
        </x-ui.reveal>
        <x-ui.reveal :delay="200" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Effectif par secteur d’activité</h3>
            <p class="mb-4 text-xs text-ink-muted">Les secteurs qui pèsent le plus sur la plateforme.</p>
            <x-charts.hbars :items="$s['sectors']" color="#0ea5e9" unit="employés" />
        </x-ui.reveal>
    </div>

    {{-- Adoption + alertes --}}
    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="140" class="rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Adoption des modules</h3>
            <p class="mb-3 text-xs text-ink-muted">Part des entreprises de chaque formule qui utilisent réellement le module (au moins un enregistrement).</p>
            <x-charts.heat :cols="$s['adoption']['cols']" :rows="$s['adoption']['rows']" />
        </x-ui.reveal>
        <x-ui.reveal :delay="220" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">À traiter</h3>
            <ul class="space-y-3">
                @forelse ($s['alerts'] as $a)
                    <li class="flex gap-3 text-sm" wire:key="al-{{ $loop->index }}">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot[$a['level']] }}"></span>
                        <div class="min-w-0">
                            @if (auth()->user()->platformCan('companies.view'))<a href="{{ route('platform.companies.show', $a['company_id']) }}" wire:navigate class="font-medium hover:underline">{{ $a['company'] }}</a>@else<span class="font-medium">{{ $a['company'] }}</span>@endif
                            <p class="text-xs text-ink-muted">{{ $a['text'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-ink-muted">Rien à signaler.</li>
                @endforelse
            </ul>
        </x-ui.reveal>
    </div>

    {{-- Tableau croisé (TCD) --}}
    <x-ui.reveal :delay="160" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold">Tableau croisé</h3>
                <p class="text-xs text-ink-muted">Choisissez les lignes et la mesure : le tableau se recalcule instantanément.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <select wire:model.live="pivotBy" class="input py-1.5 text-sm"><option value="sector">Par secteur</option><option value="country">Par pays</option><option value="period">Par période de facturation</option></select>
                <select wire:model.live="pivotMetric" class="input py-1.5 text-sm"><option value="companies">Nombre d’entreprises</option><option value="employees">Effectif</option><option value="mrr">Revenu mensuel (FCFA)</option></select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-ink-muted">
                    <tr class="border-b border-line"><th class="py-2 pr-4 text-left font-medium">{{ ['sector' => 'Secteur', 'country' => 'Pays', 'period' => 'Période'][$pivotBy] }}</th>@foreach ($pivot['cols'] as $name)<th class="px-3 py-2 text-right font-medium">{{ $name }}</th>@endforeach<th class="px-3 py-2 text-right font-semibold text-ink">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($pivot['rows'] as $label => $cells)
                        <tr wire:key="pv-{{ $loop->index }}-{{ $pivotBy }}">
                            <td class="py-2 pr-4 font-medium">{{ $label }}</td>
                            @foreach ($pivot['cols'] as $code => $name)<td class="px-3 py-2 text-right text-ink-muted">{{ $cell($cells[$code] ?? 0) }}</td>@endforeach
                            <td class="px-3 py-2 text-right font-semibold">{{ $cell($cells['_total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($pivot['cols']) + 2 }}" class="py-8 text-center text-ink-muted">Aucune entreprise pour le moment.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr class="border-t-2 border-line font-semibold"><td class="py-2 pr-4">Total</td>@foreach ($pivot['cols'] as $code => $name)<td class="px-3 py-2 text-right">{{ $cell($pivot['totals'][$code] ?? 0) }}</td>@endforeach<td class="px-3 py-2 text-right">{{ $cell($pivot['totals']['_total']) }}</td></tr></tfoot>
            </table>
        </div>
    </x-ui.reveal>
</div>
