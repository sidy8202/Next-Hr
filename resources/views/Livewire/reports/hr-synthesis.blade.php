@php
    $cur = $s['cur'];
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
    $dec = fn ($v) => $v === null ? '—' : str_replace('.', ',', (string) $v);
    $hasPrev = $s['prev'] !== null;
    $rows = [
        ['Effectif en fin de période', 'head_end', false],
        ['Arrivées', 'entries', false],
        ['Départs', 'exits', false],
        ['Taux de rotation (%)', 'turnover', true],
        ['Ancienneté moyenne (ans)', 'seniority_avg', true],
        ['Jours de congé pris', 'leave_days', false],
    ];
@endphp
<div class="space-y-6">
    @if ($s['quality'] > 0)
        <p class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $s['quality'] }} employé(s) sont inactifs mais n’ont aucune sortie enregistrée : leur départ n’est pas compté. Enregistrez leur sortie (date et motif) pour fiabiliser l’effectif et la rotation.</p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-platform.kpi label="Effectif en fin de période" :value="$cur['head_end']" suffix=" employés" :delta="$s['delta']['head_end']" :hint="$hasPrev ? 'vs comparaison' : ''" color="#6366f1" :delay="0" />
        <x-platform.kpi label="Arrivées" :value="$cur['entries']" :delta="$s['delta']['entries']" :hint="$hasPrev ? 'vs comparaison' : 'embauches sur la période'" color="#10b981" :delay="60" />
        <x-platform.kpi label="Départs" :value="$cur['exits']" :hint="$hasPrev ? 'comparaison : '.$s['prev']['exits'] : 'sorties sur la période'" color="#f43f5e" :delay="120" />
        <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-ink-muted">Taux de rotation</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">{{ $cur['turnover'] === null ? '—' : $dec($cur['turnover']).' %' }}</p>
            <p class="mt-1 text-xs text-ink-muted">@if ($s['delta']['turnover_pts'] !== null)<span class="font-semibold {{ $s['delta']['turnover_pts'] <= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $s['delta']['turnover_pts'] > 0 ? '▲ +' : ($s['delta']['turnover_pts'] < 0 ? '▼ ' : '') }}{{ $dec($s['delta']['turnover_pts']) }} pt</span> @endif départs ÷ effectif moyen</p>
        </x-ui.reveal>
        <x-ui.reveal :delay="240" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-ink-muted">Ancienneté moyenne</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">{{ $cur['seniority_avg'] === null ? '—' : $dec($cur['seniority_avg']).' ans' }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $cur['women_pct'] !== null ? $cur['women_pct'].' % de femmes' : '' }}{{ $cur['age_avg'] !== null ? ' · âge moyen '.$dec($cur['age_avg']).' ans' : '' }}</p>
        </x-ui.reveal>
        <x-platform.kpi label="Jours de congé pris" :value="$cur['leave_days']" suffix=" j" :delta="$s['delta']['leave_days']" :hint="$hasPrev ? 'vs comparaison' : 'congés approuvés débutant sur la période'" color="#0ea5e9" :delay="300" />
        @if ($canSalary)
            <x-platform.kpi label="Masse salariale de base (mensuelle)" :value="$cur['salary_mass']" suffix=" FCFA" :delta="$s['delta']['salary_mass']" :hint="$cur['salary_known'].' salaire(s) de contrat renseigné(s)'" color="#8b5cf6" :delay="360" />
        @endif
    </div>

    @if (count($s['insights']))
        <x-ui.reveal :delay="150" class="print-keep rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <h3 class="mb-2 text-sm font-semibold">Ce que disent les chiffres</h3>
            <ul class="space-y-1.5 text-sm">@foreach ($s['insights'] as $line)<li class="flex gap-2"><span class="text-indigo-600">▸</span><span>{{ $line }}</span></li>@endforeach</ul>
        </x-ui.reveal>
    @endif

    <div class="grid gap-4 xl:grid-cols-2">
        <x-ui.reveal :delay="100" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Évolution de l’effectif</h3>
            <p class="mb-3 text-xs text-ink-muted">Effectif à la fin de chaque mois.</p>
            <x-charts.trend :labels="$s['monthly']['labels']" unit="employés" :series="[['name' => 'Effectif', 'color' => '#6366f1', 'values' => $s['monthly']['head'], 'fill' => true]]" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Arrivées et départs</h3>
            <p class="mb-3 text-xs text-ink-muted">Les mouvements de personnel, mois par mois.</p>
            <x-charts.grouped :labels="$s['monthly']['labels']" :series="[['name' => 'Arrivées', 'color' => '#10b981', 'values' => $s['monthly']['entries']], ['name' => 'Départs', 'color' => '#f43f5e', 'values' => $s['monthly']['exits']]]" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.reveal :delay="120" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Départs par département</h3>
            <p class="mb-4 text-xs text-ink-muted">Là où l’on perd le plus de monde sur la période.</p>
            <x-charts.hbars :items="$s['rotation']" color="#f43f5e" unit="départ(s)" />
        </x-ui.reveal>
        <x-ui.reveal :delay="200" class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">À surveiller</h3>
            <div class="space-y-4 text-sm">
                <div><p class="mb-1 text-xs font-semibold text-amber-700">Contrats qui se terminent sous 60 jours ({{ count($s['alerts']['contract_end']) }})</p>
                    <ul class="space-y-1">@forelse (array_slice($s['alerts']['contract_end'], 0, 6) as $a)<li class="flex justify-between gap-2"><span>{{ $a['label'] }} <span class="text-xs text-ink-muted">· {{ $a['type'] }}</span></span><span class="text-xs text-ink-muted">{{ \Carbon\Carbon::parse($a['date'])->format('d/m/Y') }}</span></li>@empty<li class="text-xs text-ink-muted">Aucun.</li>@endforelse</ul></div>
                <div><p class="mb-1 text-xs font-semibold text-sky-700">Fins de période d’essai sous 30 jours ({{ count($s['alerts']['trial_end']) }})</p>
                    <ul class="space-y-1">@forelse (array_slice($s['alerts']['trial_end'], 0, 6) as $a)<li class="flex justify-between gap-2"><span>{{ $a['label'] }}</span><span class="text-xs text-ink-muted">{{ \Carbon\Carbon::parse($a['date'])->format('d/m/Y') }}</span></li>@empty<li class="text-xs text-ink-muted">Aucune.</li>@endforelse</ul></div>
                <div><p class="mb-1 text-xs font-semibold text-violet-700">Permis de travail qui expirent sous 60 jours ({{ count($s['alerts']['permit']) }})</p>
                    <ul class="space-y-1">@forelse (array_slice($s['alerts']['permit'], 0, 6) as $a)<li class="flex justify-between gap-2"><span>{{ $a['label'] }}</span><span class="text-xs text-ink-muted">{{ \Carbon\Carbon::parse($a['date'])->format('d/m/Y') }}</span></li>@empty<li class="text-xs text-ink-muted">Aucun.</li>@endforelse</ul></div>
                @if ($s['alerts']['no_contract'] > 0)<p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">{{ $s['alerts']['no_contract'] }} employé(s) en poste n’ont aucun contrat enregistré.</p>@endif
            </div>
        </x-ui.reveal>
    </div>

    @if ($hasPrev)
        <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-1 text-sm font-semibold">Comparaison de périodes</h3>
            <p class="mb-3 text-xs text-ink-muted">Période choisie ({{ $s['from']->format('d/m/Y') }} → {{ $s['to']->format('d/m/Y') }}) contre comparaison ({{ $s['pFrom']->format('d/m/Y') }} → {{ $s['pTo']->format('d/m/Y') }}).</p>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Indicateur</th><th class="px-3 py-2 text-right font-medium">Période</th><th class="px-3 py-2 text-right font-medium">Comparaison</th><th class="px-3 py-2 text-right font-medium">Évolution</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as [$label, $k, $decimal])
                        <tr><td class="py-2 pr-3">{{ $label }}</td><td class="px-3 py-2 text-right font-medium">{{ $decimal ? $dec($cur[$k]) : $cur[$k] }}</td><td class="px-3 py-2 text-right text-ink-muted">{{ $decimal ? $dec($s['prev'][$k]) : $s['prev'][$k] }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-ink-muted">@if ($k === 'turnover'){{ $s['delta']['turnover_pts'] === null ? '—' : ($s['delta']['turnover_pts'] > 0 ? '+' : '').$dec($s['delta']['turnover_pts']).' pt' }}@else{{ ($s['delta'][$k] ?? null) === null ? '—' : ($s['delta'][$k] > 0 ? '+' : '').$s['delta'][$k].' %' }}@endif</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif

    <section class="print-keep rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Mouvements par département</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 text-left font-medium">Département</th><th class="px-3 py-2 text-right font-medium">Effectif</th><th class="px-3 py-2 text-right font-medium">Arrivées</th><th class="px-3 py-2 text-right font-medium">Départs</th><th class="px-3 py-2 text-right font-medium">Rotation</th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($s['by_dept'] as $r)
                    <tr wire:key="bd-{{ $loop->index }}"><td class="py-2 pr-3 font-medium">{{ $r['label'] }}</td><td class="px-3 py-2 text-right">{{ $r['head'] }}</td><td class="px-3 py-2 text-right">{{ $r['entries'] }}</td><td class="px-3 py-2 text-right">{{ $r['exits'] }}</td><td class="px-3 py-2 text-right {{ ($r['turnover'] ?? 0) > 15 ? 'font-semibold text-rose-600' : '' }}">{{ $r['turnover'] === null ? '—' : $dec($r['turnover']).' %' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-ink-muted">Aucune donnée pour ces critères.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr class="border-t-2 border-line font-semibold"><td class="py-2 pr-3">Total</td><td class="px-3 py-2 text-right">{{ $cur['head_end'] }}</td><td class="px-3 py-2 text-right">{{ $cur['entries'] }}</td><td class="px-3 py-2 text-right">{{ $cur['exits'] }}</td><td class="px-3 py-2 text-right">{{ $cur['turnover'] === null ? '—' : $dec($cur['turnover']).' %' }}</td></tr></tfoot>
        </table></div>
    </section>
</div>
