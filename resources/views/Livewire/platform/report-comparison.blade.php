@php
    $items = collect($cmp['items']);
    $palette = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9'];
    $metricLabel = \App\Services\PlatformReports::COMPARE_METRICS[$cmpMetric] ?? 'Encaissements';
    $mKey = array_key_exists($cmpMetric, \App\Services\PlatformReports::COMPARE_METRICS) ? $cmpMetric : 'revenue';
    $isMoney = in_array($mKey, ['revenue', 'mrr', 'arpa'], true);
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
@endphp
<div class="space-y-6">
    <section class="no-print space-y-3 rounded-2xl border border-line bg-surface p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Comparer des…</label>
                <select wire:model.live="cmpDim" class="input py-1.5 text-sm">@foreach (\App\Services\PlatformReports::COMPARE_DIMS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-medium text-ink-muted">Mesure du graphique</label>
                <select wire:model.live="cmpMetric" class="input py-1.5 text-sm">@foreach (\App\Services\PlatformReports::COMPARE_METRICS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <p class="pb-2 text-xs text-ink-muted">Cochez jusqu’à 5 éléments ({{ count($cmpValues) }}/5).</p>
        </div>
        <div class="flex max-h-40 flex-wrap gap-x-5 gap-y-1.5 overflow-y-auto">
            @foreach ($cmp['candidates'] as $key => $label)
                <label class="flex items-center gap-2 text-sm" wire:key="cd-{{ $cmpDim }}-{{ $key }}"><input type="checkbox" wire:model.live="cmpValues" value="{{ $key }}" class="rounded"> {{ $label }}</label>
            @endforeach
        </div>
    </section>

    @if ($items->isEmpty())
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Cochez au moins un élément à comparer.</p>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            <x-ui.reveal class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold">{{ $metricLabel }}</h3>
                <p class="mb-3 text-xs text-ink-muted">Pour chaque élément comparé, sur la période choisie.</p>
                <x-charts.grouped :labels="$items->pluck('label')->all()" :unit="$isMoney ? 'FCFA' : ''" :series="[['name' => '', 'color' => '#6366f1', 'values' => $items->pluck($mKey)->all()]]" />
            </x-ui.reveal>
            <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold">Évolution des encaissements</h3>
                <p class="mb-3 text-xs text-ink-muted">Mois par mois, élément par élément.</p>
                <x-charts.trend :labels="$cmp['month_labels']" unit="FCFA" :series="$items->values()->map(fn ($it, $i) => ['name' => $it['label'], 'color' => $palette[$i % 5], 'values' => $it['series']])->all()" />
            </x-ui.reveal>
        </div>

        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Tableau comparatif</h3>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">{{ \App\Services\PlatformReports::COMPARE_DIMS[$cmp['dim']] }}</th><th class="px-3 py-2 text-right font-medium">Entreprises</th><th class="px-3 py-2 text-right font-medium">Effectif</th><th class="px-3 py-2 text-right font-medium">Encaissements</th><th class="px-3 py-2 text-right font-medium">MRR actuel</th><th class="px-3 py-2 text-right font-medium">Souscriptions</th><th class="px-3 py-2 text-right font-medium">Revenu moyen</th><th class="px-3 py-2 text-right font-medium">Taux de perte</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($items as $it)
                        <tr wire:key="ci-{{ $it['key'] }}">
                            <td class="py-2 pr-3 font-medium"><span class="mr-2 inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $palette[$loop->index % 5] }}"></span>{{ $it['label'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $it['companies'] }}</td><td class="px-3 py-2 text-right">{{ $it['employees'] }}</td><td class="px-3 py-2 text-right">{{ $fcfa($it['revenue']) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($it['mrr']) }}</td><td class="px-3 py-2 text-right">{{ $it['new_subs'] }}</td><td class="px-3 py-2 text-right">{{ $fcfa($it['arpa']) }}</td><td class="px-3 py-2 text-right">{{ $it['churn_rate'] === null ? '—' : $it['churn_rate'].' %' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="border-t-2 border-line font-semibold"><td class="py-2 pr-3">Total</td><td class="px-3 py-2 text-right">{{ $items->sum('companies') }}</td><td class="px-3 py-2 text-right">{{ $items->sum('employees') }}</td><td class="px-3 py-2 text-right">{{ $fcfa($items->sum('revenue')) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($items->sum('mrr')) }}</td><td class="px-3 py-2 text-right">{{ $items->sum('new_subs') }}</td><td></td><td></td></tr></tfoot>
            </table></div>
        </section>
    @endif
</div>
