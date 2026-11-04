@php
    $m = $ret['metrics'];
    $card = fn ($label, $value, $hint) => compact('label', 'value', 'hint');
    $cards = [
        $card('Conversion essai → payant', $m['trial_rate'] === null ? '—' : $m['trial_rate'].' %', $m['trial_won'].' converti(s), '.$m['trial_lost'].' perdu(s)'),
        $card('Taux de renouvellement', $m['renewal_rate'] === null ? '—' : $m['renewal_rate'].' %', $m['renewals'].' renouvellement(s) sur '.$m['due'].' échéance(s)'),
        $card('Taux de perte', $m['churn_rate'] === null ? '—' : $m['churn_rate'].' %', $m['churned'].' perdue(s) sur '.$m['active_start'].' payante(s) au départ'),
        $card('Ancienneté moyenne', $ret['avg_life'] === null ? '—' : $ret['avg_life'].' mois', 'durée de vie moyenne d’un client'),
    ];
@endphp
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $c)
            <x-ui.reveal :delay="$loop->index * 60" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                <p class="text-xs font-medium text-ink-muted">{{ $c['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tracking-tight">{{ $c['value'] }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $c['hint'] }}</p>
            </x-ui.reveal>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-ui.reveal :delay="100" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Entonnoir : de l’inscription à la fidélité</h3>
            <p class="mb-4 text-xs text-ink-muted">Parcours des entreprises créées sur la période choisie.</p>
            <x-charts.hbars :items="collect($ret['stages'])->map(fn ($s) => ['label' => $s['label'], 'value' => $s['value'], 'sub' => $s['of_first'] === null ? null : $s['of_first'].' % des créées'])->all()" color="#10b981" unit="entreprises" />
        </x-ui.reveal>
        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Taux de passage d’une étape à la suivante</h3>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Étape</th><th class="px-3 py-2 text-right font-medium">Entreprises</th><th class="px-3 py-2 text-right font-medium">Depuis l’étape précédente</th><th class="px-3 py-2 text-right font-medium">Depuis le début</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($ret['stages'] as $s)
                        <tr><td class="py-2 pr-3 font-medium">{{ $s['label'] }}</td><td class="px-3 py-2 text-right">{{ $s['value'] }}</td><td class="px-3 py-2 text-right">{{ $s['of_prev'] === null ? '—' : $s['of_prev'].' %' }}</td><td class="px-3 py-2 text-right">{{ $s['of_first'] === null ? '—' : $s['of_first'].' %' }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    </div>

    <x-ui.reveal :delay="160" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold">Fidélisation par cohorte</h3>
        <p class="mb-3 text-xs text-ink-muted">Chaque ligne regroupe les entreprises arrivées le même mois (effectif entre parenthèses). M0 = mois d’arrivée ; chaque case = part encore couverte (payée ou en essai) le mois suivant.</p>
        <x-charts.matrix corner="Cohorte" :cols="array_map(fn ($k) => 'M'.$k, range(0, 11))" :rows="$ret['cohorts']" format="pct" color="16,185,129" />
    </x-ui.reveal>
</div>
