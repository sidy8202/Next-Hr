@php($fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA')
<div class="space-y-6">
    <p class="text-sm text-ink-muted">Les échéances des 90 prochains jours (abonnements actifs, essais et paiements déjà en retard), en supposant que chaque entreprise renouvelle au même prix. Cette vue ne dépend pas de la période choisie ; les autres filtres s’appliquent.</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Attendu sous 30 jours', $fc['d30'], '#10b981'], ['Attendu sous 60 jours', $fc['d60'], '#0ea5e9'], ['Attendu sous 90 jours', $fc['d90'], '#6366f1'], ['Déjà en retard', $fc['at_risk'], '#f43f5e']] as [$label, $value, $color])
            <x-platform.kpi :label="$label" :value="$value" suffix=" FCFA" :color="$color" :delay="$loop->index * 60" :hint="$loop->last ? $fc['at_risk_count'].' entreprise(s) à relancer' : ''" />
        @endforeach
    </div>

    <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Encaissements attendus par mois</h3>
        <p class="mb-3 text-xs text-ink-muted">Les retards sont comptés dans le mois en cours.</p>
        <x-charts.grouped :labels="array_column($fc['buckets'], 'label')" unit="FCFA" :series="[['name' => '', 'color' => '#10b981', 'values' => array_column($fc['buckets'], 'value')]]" />
    </x-ui.reveal>

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Échéances à venir ({{ $fc['count'] }})</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Entreprise</th><th class="px-3 py-2 text-left font-medium">Formule</th><th class="px-3 py-2 text-left font-medium">Statut</th><th class="px-3 py-2 text-left font-medium">Échéance</th><th class="px-3 py-2 text-right font-medium">Montant attendu</th><th class="px-3 py-2 text-left font-medium">Signal</th><th class="no-print px-3 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($fc['rows'] as $r)
                    <tr wire:key="fc-{{ $r['company_id'] }}">
                        <td class="py-2 pr-3 font-medium">{{ $r['company'] }}</td><td class="px-3 py-2">{{ $r['plan'] }}</td><td class="px-3 py-2">{{ $r['status_label'] }}</td>
                        <td class="px-3 py-2">{{ $r['end']->format('d/m/Y') }}<span class="block text-xs {{ $r['days'] < 0 ? 'text-rose-600' : 'text-ink-muted' }}">{{ $r['days'] < 0 ? abs($r['days']).' j de retard' : 'dans '.$r['days'].' j' }}</span></td>
                        <td class="px-3 py-2 text-right">{{ $fcfa($r['amount']) }}</td>
                        <td class="px-3 py-2 text-xs {{ $r['status'] === 'past_due' ? 'text-rose-600' : 'text-ink-muted' }}">{{ $r['risk'] }}</td>
                        <td class="no-print px-3 py-2 text-right"><a href="{{ route('platform.companies.show', $r['company_id']) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-ink-muted">Aucune échéance dans les 90 prochains jours.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
</div>
