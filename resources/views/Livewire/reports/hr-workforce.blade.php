@php
    $dec = fn ($v) => $v === null ? '—' : str_replace('.', ',', (string) $v);
@endphp
<div class="space-y-6">
    @if ($w['quality'] > 0)
        <p class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $w['quality'] }} employé(s) inactif(s) sans sortie enregistrée sont exclus de l’effectif. Enregistrez leur sortie pour fiabiliser ces chiffres.</p>
    @endif

    <x-ui.reveal class="text-sm text-ink-muted">Effectif au <strong class="text-ink">{{ \Carbon\Carbon::parse($w['end'])->format('d/m/Y') }}</strong> : <strong class="text-ink">{{ $w['total'] }}</strong> employé(s) en poste.</x-ui.reveal>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="60" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Effectif par département</h3>
            <p class="mb-4 text-xs text-ink-muted">Les 10 plus importants.</p>
            <x-charts.hbars :items="$w['dept_bars']" color="#0ea5e9" unit="employés" />
        </x-ui.reveal>
        <x-ui.reveal :delay="140" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Types de contrat</h3>
            <x-charts.donut :items="$w['contracts']" :center="$w['total']" center-label="employés" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.reveal :delay="80" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Femmes et hommes</h3>
            <x-charts.donut :items="$w['gender']" :center="$w['total']" center-label="employés" />
        </x-ui.reveal>
        <x-ui.reveal :delay="160" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Ancienneté</h3>
            <p class="mb-3 text-xs text-ink-muted">Nombre d’employés par tranche.</p>
            <x-charts.grouped :labels="$w['seniority']['labels']" :series="[['name' => '', 'color' => '#6366f1', 'values' => $w['seniority']['values']]]" unit="employé(s)" :height="220" />
        </x-ui.reveal>
        <x-ui.reveal :delay="240" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Âge</h3>
            <p class="mb-3 text-xs text-ink-muted">Nombre d’employés par tranche d’âge.</p>
            <x-charts.grouped :labels="$w['ages']['labels']" :series="[['name' => '', 'color' => '#8b5cf6', 'values' => $w['ages']['values']]]" unit="employé(s)" :height="220" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="100" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Département × ancienneté</h3>
            <p class="mb-3 text-xs text-ink-muted">Tableau croisé : plus la case est foncée, plus l’effectif est important.</p>
            <x-charts.matrix corner="Département" :cols="$w['matrix_cols']" :rows="$w['matrix']" />
        </x-ui.reveal>
        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Postes les plus occupés</h3>
            <ul class="divide-y divide-line text-sm">
                @forelse ($w['positions'] as $p)
                    <li class="flex items-center justify-between gap-2 py-1.5" wire:key="ps-{{ $loop->index }}"><span>{{ $p['label'] }}</span><span class="text-xs text-ink-muted"><strong class="text-ink">{{ $p['value'] }}</strong> · {{ $p['share'] }} %</span></li>
                @empty
                    <li class="py-6 text-center text-ink-muted">Aucune donnée.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Détail par département</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Département</th><th class="px-3 py-2 text-right font-medium">Effectif</th><th class="px-3 py-2 text-right font-medium">Ancienneté moyenne</th><th class="px-3 py-2 text-right font-medium">Âge moyen</th><th class="px-3 py-2 text-right font-medium">Femmes</th><th class="px-3 py-2 text-right font-medium">Arrivées</th><th class="px-3 py-2 text-right font-medium">Départs</th><th class="px-3 py-2 text-right font-medium">Rotation</th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($w['table'] as $r)
                    <tr wire:key="wt-{{ $loop->index }}"><td class="py-2 pr-3 font-medium">{{ $r['label'] }}</td><td class="px-3 py-2 text-right">{{ $r['head'] }}</td><td class="px-3 py-2 text-right">{{ $dec($r['seniority']) }}{{ $r['seniority'] === null ? '' : ' ans' }}</td><td class="px-3 py-2 text-right">{{ $dec($r['age']) }}{{ $r['age'] === null ? '' : ' ans' }}</td><td class="px-3 py-2 text-right">{{ $r['women'] === null ? '—' : $r['women'].' %' }}</td><td class="px-3 py-2 text-right">{{ $r['entries'] }}</td><td class="px-3 py-2 text-right">{{ $r['exits'] }}</td><td class="px-3 py-2 text-right">{{ $r['turnover'] === null ? '—' : $dec($r['turnover']).' %' }}</td></tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-ink-muted">Aucune donnée pour ces critères.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
</div>
