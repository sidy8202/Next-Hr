<div class="space-y-6">
    @if (! $cost['hasData'])
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Aucune paie calculée sur cette période, avec ces critères. Le rapport se remplit dès qu’un mois est calculé.</p>
    @else
        @if ($cost['kpis']['note'])
            <p class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $cost['kpis']['note'] }}</p>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cost['kpis']['items'] as $k)
                <div wire:key="ck-{{ $k['key'] }}">
                    <x-platform.kpi :label="$k['label']" :value="$k['value']" :suffix="$k['suffix']" :delta="$k['delta']" :hint="$k['hint']" :color="$k['color']" :delay="$loop->index * 60" />
                </div>
            @endforeach
        </div>

        @if (count($cost['sentences']))
            <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10">
                <h3 class="mb-2 text-sm font-semibold">Ce que disent les chiffres</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($cost['sentences'] as $line)
                        <li wire:key="cs-{{ $loop->index }}" class="flex gap-2"><span class="text-indigo-600">▸</span><span>{{ $line['text'] }}<span class="block text-xs text-ink-muted">Règle : {{ $line['rule'] }}</span></span></li>
                    @endforeach
                </ul>
            </x-ui.reveal>
        @endif

        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold">Évolution mois par mois</h3>
                    <p class="text-xs text-ink-muted">{{ ucfirst($cost['metricLabel']) }}, en FCFA. Les mois sans bulletin calculé ne sont pas affichés.</p>
                </div>
                <div class="no-print inline-flex rounded-lg border border-line p-0.5" role="group" aria-label="Indicateur affiché">
                    @foreach ($cost['metrics'] as $key => $label)
                        <button type="button" wire:key="cm-{{ $key }}" wire:click="setMetric('{{ $key }}')" class="rounded-md px-3 py-1 text-xs font-medium transition {{ $cost['metric'] === $key ? 'bg-indigo-600 text-white' : 'text-ink-muted hover:bg-surface-muted' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <x-charts.trend :labels="$cost['chart']['labels']" unit="FCFA" :series="[['name' => ucfirst($cost['metricLabel']), 'color' => '#6366f1', 'values' => $cost['chart']['values'], 'fill' => true]]" />
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold">Répartition par service</h3>
                <p class="mb-4 text-xs text-ink-muted">{{ ucfirst($cost['metricLabel']) }} cumulé sur la période, avec la part du total.</p>
                @if (count($cost['deptBars']))
                    <x-charts.hbars :items="$cost['deptBars']" color="#6366f1" unit="FCFA" />
                @else
                    <p class="rounded-xl bg-surface-muted/60 px-4 py-6 text-center text-sm text-ink-muted">Aucun service n’atteint l’effectif minimal d’affichage ({{ $cost['minGroup'] }} personnes).</p>
                @endif
                @if ($cost['departments']['hidden_people'] > 0)
                    <p class="mt-3 text-xs text-ink-muted">{{ $cost['departments']['hidden_people'] }} {{ $cost['departments']['hidden_people'] > 1 ? 'personnes' : 'personne' }} de petits services {{ $cost['departments']['hidden_people'] > 1 ? 'ne sont pas affichées' : 'n’est pas affichée' }} : sous {{ $cost['minGroup'] }} personnes, le total d’un service révélerait un salaire.</p>
                @endif
            </section>

            <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold">Détail par mois</h3>
                <div class="max-h-96 overflow-auto">
                    <table class="w-full min-w-[480px] text-sm">
                        <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Mois</th><th class="px-3 py-2 text-right font-medium">Bulletins</th><th class="px-3 py-2 text-right font-medium">Masse brute</th><th class="px-3 py-2 text-right font-medium">Net à payer</th><th class="px-3 py-2 text-right font-medium">Coût employeur</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($cost['table'] as $r)
                                <tr wire:key="ct-{{ $r['label'] }}">
                                    <td class="py-2 pr-3">{{ $r['label'] }}@if ($r['status'] === 'Provisoire') <span class="text-xs text-amber-600">(provisoire)</span>@endif</td>
                                    <td class="px-3 py-2 text-right">{{ $r['n'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Services\Payroll\PayrollDashboardStats::number($r['gross']) }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Services\Payroll\PayrollDashboardStats::number($r['net_to_pay']) }}</td>
                                    <td class="px-3 py-2 text-right font-medium">{{ \App\Services\Payroll\PayrollDashboardStats::number($r['employer_cost']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @if ($cost['seasonNote'])
            <p class="text-xs text-ink-muted">{{ $cost['seasonNote'] }}</p>
        @endif
    @endif

    @include('livewire.reports.report-thresholds')
</div>
