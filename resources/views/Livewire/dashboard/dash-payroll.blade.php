@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
@endphp
<div class="space-y-6">
    @if (! $d['available'])
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">Aucun salaire de base n’est encore renseigné dans les contrats : la vue Paie se remplira dès que les salaires seront saisis.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-platform.kpi label="Masse salariale de base (mensuelle)" :value="$d['total']" suffix=" FCFA" color="#6366f1" hint="somme des salaires bruts de base des contrats" :delay="0" />
            <x-platform.kpi label="Salaire de base moyen" :value="$d['avg']" suffix=" FCFA" color="#0ea5e9" :delay="60" />
            <x-platform.kpi label="Salaires renseignés" :value="$d['count']" :suffix="' / '.$d['active']" color="#10b981" hint="employés actifs avec un salaire de base" :delay="120" />
            <x-platform.kpi label="Salaire à renseigner" :value="$d['missing']" color="#f43f5e" hint="employés actifs sans salaire de base" :delay="180" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.reveal :delay="100" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold">Masse salariale par département</h3>
                <p class="mb-4 text-xs text-ink-muted">Salaires de base mensuels, en FCFA.</p>
                <x-charts.hbars :items="$d['by_dept']" color="#6366f1" unit="FCFA" />
            </x-ui.reveal>
            <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold">Répartition par tranche de salaire</h3>
                <p class="mb-3 text-xs text-ink-muted">Nombre de salariés par tranche de salaire de base (FCFA).</p>
                <x-charts.grouped :labels="$d['bands']['labels']" :series="[['name' => '', 'color' => '#8b5cf6', 'values' => $d['bands']['values']]]" unit="salarié(s)" />
            </x-ui.reveal>
        </div>
    @endif

    <x-ui.reveal :delay="220" class="rounded-2xl border border-dashed border-indigo-300 bg-indigo-50/50 p-5 dark:border-indigo-500/40 dark:bg-indigo-500/5">
        <h3 class="text-sm font-semibold">Le cycle de paie arrive</h3>
        <p class="mt-1 text-sm text-ink-muted">Cette vue accueillera bientôt :</p>
        <ul class="mt-2 grid gap-1.5 text-sm sm:grid-cols-2">
            <li class="flex gap-2"><span class="text-indigo-600">▸</span>le net total du mois, face au net habituel</li>
            <li class="flex gap-2"><span class="text-indigo-600">▸</span>les bulletins dont le net a changé, par département</li>
            <li class="flex gap-2"><span class="text-indigo-600">▸</span>l’effet des primes, mises à pied, absences et heures supplémentaires</li>
            <li class="flex gap-2"><span class="text-indigo-600">▸</span>l’avancement : calcul, vérification, clôture, envoi des bulletins</li>
        </ul>
    </x-ui.reveal>
</div>
