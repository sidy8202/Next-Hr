@php($cur = $ov['cur'])
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-platform.kpi label="Nouvelles entreprises" :value="$cur['new_companies']" :delta="$ov['delta']['new_companies']" hint="{{ $ov['prev'] ? 'vs comparaison' : '' }}" color="#6366f1" :delay="0" />
        <x-platform.kpi label="Souscriptions créées" :value="$cur['new_subs']" :delta="$ov['delta']['new_subs']" hint="{{ $ov['prev'] ? 'vs comparaison' : 'nouvelles et changements de formule' }}" color="#0ea5e9" :delay="60" />
        <x-platform.kpi label="Encaissements" :value="$cur['revenue']" suffix=" FCFA" :delta="$ov['delta']['revenue']" :hint="$cur['payments'].' paiement(s)'" color="#10b981" :delay="120" />
        <x-platform.kpi label="Revenu mensuel récurrent (fin de période)" :value="$cur['mrr_end']" suffix=" FCFA" :delta="$ov['delta']['mrr_end']" hint="{{ $ov['prev'] ? 'vs comparaison' : '' }}" color="#8b5cf6" :delay="180" />
        <x-platform.kpi label="Revenu moyen par entreprise payante" :value="$cur['arpa']" suffix=" FCFA" :delta="$ov['delta']['arpa']" :hint="$cur['payers'].' entreprise(s) payante(s)'" color="#f59e0b" :delay="240" />
        <x-platform.kpi label="Entreprises perdues" :value="$cur['churned']" :hint="'taux de perte : '.$pctText($cur['churn_rate'])" color="#f43f5e" :delay="300" />
    </div>

    @if (count($insights))
        <x-ui.reveal :delay="150" class="print-keep rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <h3 class="mb-2 text-sm font-semibold">Ce que disent les chiffres</h3>
            <ul class="space-y-1.5 text-sm">@foreach ($insights as $line)<li class="flex gap-2"><span class="text-indigo-600">▸</span><span>{{ $line }}</span></li>@endforeach</ul>
        </x-ui.reveal>
    @endif

    <div class="grid gap-4 xl:grid-cols-2">
        <x-ui.reveal :delay="100" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Encaissements et revenu mensuel récurrent</h3>
            <p class="mb-3 text-xs text-ink-muted">Par mois, sur la période choisie.</p>
            <x-charts.trend :labels="array_column($ov['months'], 'label')" unit="FCFA" :series="[
                ['name' => 'Revenu mensuel récurrent', 'color' => '#8b5cf6', 'values' => array_column($ov['months'], 'mrr_end'), 'fill' => true],
                ['name' => 'Encaissé', 'color' => '#10b981', 'values' => array_column($ov['months'], 'revenue')],
            ]" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Nouvelles entreprises et souscriptions</h3>
            <p class="mb-3 text-xs text-ink-muted">Le rythme d’acquisition, mois par mois.</p>
            <x-charts.grouped :labels="array_column($ov['months'], 'label')" unit="" :series="[
                ['name' => 'Nouvelles entreprises', 'color' => '#6366f1', 'values' => array_column($ov['months'], 'new_companies')],
                ['name' => 'Souscriptions créées', 'color' => '#0ea5e9', 'values' => array_column($ov['months'], 'new_subs')],
            ]" />
        </x-ui.reveal>
    </div>

    @if ($ov['prev'])
        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-1 text-sm font-semibold">Comparaison de périodes</h3>
            <p class="mb-3 text-xs text-ink-muted">Période choisie ({{ $ov['from']->format('d/m/Y') }} → {{ $ov['to']->format('d/m/Y') }}) contre comparaison ({{ $ov['pFrom']->format('d/m/Y') }} → {{ $ov['pTo']->format('d/m/Y') }}).</p>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Indicateur</th><th class="px-3 py-2 text-right font-medium">Période</th><th class="px-3 py-2 text-right font-medium">Comparaison</th><th class="px-3 py-2 text-right font-medium">Évolution</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ([['Nouvelles entreprises', 'new_companies', false], ['Souscriptions créées', 'new_subs', false], ['Encaissements', 'revenue', true], ['MRR (fin de période)', 'mrr_end', true], ['Revenu moyen par entreprise', 'arpa', true], ['Entreprises perdues', 'churned', false]] as [$label, $k, $money])
                        @php($d = \App\Services\ReportPeriod::delta($cur[$k], $ov['prev'][$k]))
                        <tr><td class="py-2 pr-3">{{ $label }}</td><td class="px-3 py-2 text-right font-medium">{{ $money ? $fcfa($cur[$k]) : $cur[$k] }}</td><td class="px-3 py-2 text-right text-ink-muted">{{ $money ? $fcfa($ov['prev'][$k]) : $ov['prev'][$k] }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $d === null ? 'text-ink-muted' : (($k === 'churned' ? $d <= 0 : $d >= 0) ? 'text-emerald-600' : 'text-rose-600') }}">{{ $d === null ? '—' : ($d > 0 ? '+' : '').$d.' %' }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Détail mensuel</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Mois</th><th class="px-3 py-2 text-right font-medium">Nouvelles entreprises</th><th class="px-3 py-2 text-right font-medium">Souscriptions</th><th class="px-3 py-2 text-right font-medium">Encaissements</th><th class="px-3 py-2 text-right font-medium">MRR fin de mois</th><th class="px-3 py-2 text-right font-medium">Perdues</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach ($ov['months'] as $row)
                    <tr wire:key="om-{{ $loop->index }}"><td class="py-2 pr-3 font-medium">{{ $row['label'] }}</td><td class="px-3 py-2 text-right">{{ $row['new_companies'] }}</td><td class="px-3 py-2 text-right">{{ $row['new_subs'] }}</td><td class="px-3 py-2 text-right">{{ $fcfa($row['revenue']) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($row['mrr_end']) }}</td><td class="px-3 py-2 text-right">{{ $row['churned'] }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr class="border-t-2 border-line font-semibold"><td class="py-2 pr-3">Total période</td><td class="px-3 py-2 text-right">{{ $cur['new_companies'] }}</td><td class="px-3 py-2 text-right">{{ $cur['new_subs'] }}</td><td class="px-3 py-2 text-right">{{ $fcfa($cur['revenue']) }}</td><td class="px-3 py-2 text-right">{{ $fcfa($cur['mrr_end']) }}</td><td class="px-3 py-2 text-right">{{ $cur['churned'] }}</td></tr></tfoot>
        </table></div>
    </section>
</div>
