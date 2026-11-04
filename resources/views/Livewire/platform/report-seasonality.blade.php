@php($isMoney = $season['metric'] === 'revenue')
<div class="space-y-6">
    <div class="no-print flex flex-wrap items-end gap-3">
        <div><label class="mb-1 block text-xs font-medium text-ink-muted">Ce qu’on observe</label>
            <select wire:model.live="seasonMetric" class="input py-1.5 text-sm"><option value="subs">Souscriptions créées</option><option value="companies">Nouvelles entreprises</option><option value="revenue">Encaissements</option></select></div>
        <p class="pb-2 text-xs text-ink-muted">Sur tout l’historique (la période choisie plus haut ne s’applique pas ici) ; les autres filtres s’appliquent.</p>
    </div>

    <x-ui.reveal class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Carte de chaleur : année × mois</h3>
        <p class="mb-3 text-xs text-ink-muted">Plus la case est foncée, plus le mois est fort. Repérez les mois qui reviennent chaque année.</p>
        <x-charts.matrix corner="Année" :cols="array_column($season['months'], 'short')" :rows="collect($season['grid'])->map(fn ($r) => array_values($r))->all()" :format="$isMoney ? 'money' : 'int'" />
    </x-ui.reveal>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="100" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Total par mois de l’année</h3>
            <p class="mb-3 text-xs text-ink-muted">Toutes années confondues.</p>
            <x-charts.grouped :labels="array_column($season['months'], 'short')" :unit="$isMoney ? 'FCFA' : ''" :series="[['name' => '', 'color' => '#6366f1', 'values' => array_column($season['months'], 'value')]]" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Par trimestre</h3>
            <x-charts.donut :items="collect($season['quarters'])->map(fn ($q, $i) => ['label' => $q['label'], 'value' => $q['value'], 'color' => ['#6366f1', '#10b981', '#f59e0b', '#0ea5e9'][$i]])->all()" />
        </x-ui.reveal>
    </div>

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Classement des mois</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Rang</th><th class="px-3 py-2 text-left font-medium">Mois</th><th class="px-3 py-2 text-right font-medium">Total</th><th class="px-3 py-2 text-right font-medium">Part</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach (collect($season['months'])->sortByDesc('value')->values() as $i => $m)
                    <tr wire:key="sm-{{ $m['n'] }}"><td class="py-2 pr-3 text-ink-muted">{{ $i + 1 }}</td><td class="px-3 py-2 font-medium capitalize">{{ $m['label'] }}</td><td class="px-3 py-2 text-right">{{ $isMoney ? number_format($m['value'], 0, ',', ' ').' FCFA' : $m['value'] }}</td><td class="px-3 py-2 text-right">{{ $m['share'] }} %</td></tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
</div>
