@php
    $measures = ['companies' => ['Entreprises', 'entreprises', false], 'employees' => ['Effectif', 'employés', false], 'mrr' => ['Revenu mensuel récurrent actuel', 'FCFA', true], 'revenue' => ['Encaissements de la période', 'FCFA', true], 'new' => ['Nouvelles entreprises', '', false], 'churned' => ['Entreprises perdues', '', false]];
    $mKey = array_key_exists($segMetric, $measures) ? $segMetric : 'companies';
    [$mLabel, $mUnit] = $measures[$mKey];
    $rows = collect($seg['rows']);
    $colors = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#14b8a6', '#94a3b8'];
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
@endphp
<div class="space-y-6">
    <div class="no-print flex flex-wrap items-end gap-3">
        <div><label class="mb-1 block text-xs font-medium text-ink-muted">Regrouper par</label>
            <select wire:model.live="segDim" class="input py-1.5 text-sm">@foreach (\App\Services\PlatformReports::DIMENSIONS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs font-medium text-ink-muted">Classer selon</label>
            <select wire:model.live="segMetric" class="input py-1.5 text-sm">@foreach ($measures as $k => $m)<option value="{{ $k }}">{{ $m[0] }}</option>@endforeach</select></div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">{{ $mLabel }} par {{ strtolower($seg['label']) }}</h3>
            <p class="mb-4 text-xs text-ink-muted">Classement des segments sur les critères choisis.</p>
            <x-charts.hbars :items="$rows->sortByDesc($mKey)->take(10)->map(fn ($r) => ['label' => $r['label'], 'value' => $r[$mKey], 'sub' => $r['companies'].' entreprise(s)'])->values()->all()" color="#6366f1" :unit="$mUnit" />
        </x-ui.reveal>
        <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Répartition des entreprises</h3>
            <x-charts.donut :items="$rows->take(8)->values()->map(fn ($r, $i) => ['label' => $r['label'], 'value' => $r['companies'], 'color' => $colors[$i % 8]])->all()" :center="$rows->sum('companies')" center-label="entreprises" />
        </x-ui.reveal>
    </div>

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Tableau détaillé par {{ strtolower($seg['label']) }}</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">{{ $seg['label'] }}</th><th class="px-3 py-2 text-right font-medium">Entreprises</th><th class="px-3 py-2 text-right font-medium">Part</th><th class="px-3 py-2 text-right font-medium">Effectif</th><th class="px-3 py-2 text-right font-medium">Moyen</th><th class="px-3 py-2 text-right font-medium">MRR actuel</th><th class="px-3 py-2 text-right font-medium">Encaissements</th><th class="px-3 py-2 text-right font-medium">Revenu moyen</th><th class="px-3 py-2 text-right font-medium">Nouvelles</th><th class="px-3 py-2 text-right font-medium">Perdues</th><th class="px-3 py-2 text-right font-medium">Taux de perte</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach ($seg['rows'] as $r)
                    <tr wire:key="sg-{{ $loop->index }}-{{ $seg['dim'] }}"><td class="py-2 pr-3 font-medium">{{ $r['label'] }}</td><td class="px-3 py-2 text-right">{{ $r['companies'] }}</td><td class="px-3 py-2 text-right text-ink-muted">{{ $r['share'] }} %</td><td class="px-3 py-2 text-right">{{ $r['employees'] }}</td><td class="px-3 py-2 text-right text-ink-muted">{{ $r['avg_employees'] }}</td><td class="px-3 py-2 text-right">{{ $fcfa($r['mrr']) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($r['revenue']) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($r['arpa']) }}</td><td class="px-3 py-2 text-right">{{ $r['new'] }}</td><td class="px-3 py-2 text-right">{{ $r['churned'] }}</td><td class="px-3 py-2 text-right {{ ($r['churn_rate'] ?? 0) > 10 ? 'font-semibold text-rose-600' : '' }}">{{ $r['churn_rate'] === null ? '—' : $r['churn_rate'].' %' }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr class="border-t-2 border-line font-semibold"><td class="py-2 pr-3">Total</td><td class="px-3 py-2 text-right">{{ $rows->sum('companies') }}</td><td class="px-3 py-2 text-right">100 %</td><td class="px-3 py-2 text-right">{{ $rows->sum('employees') }}</td><td></td><td class="px-3 py-2 text-right">{{ $fcfa($rows->sum('mrr')) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($rows->sum('revenue')) }}</td><td></td><td class="px-3 py-2 text-right">{{ $rows->sum('new') }}</td><td class="px-3 py-2 text-right">{{ $rows->sum('churned') }}</td><td></td></tr></tfoot>
        </table></div>
    </section>
</div>
