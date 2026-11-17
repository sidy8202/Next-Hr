@php
    $p = $d['presence'];
    $tones = ['amber' => 'bg-amber-500', 'rose' => 'bg-rose-500', 'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500'];
@endphp
<div class="space-y-6">
    <livewire:payroll.home-card />
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-platform.kpi label="Effectif actif" :value="$p['active']" suffix=" employés" color="#6366f1" :hint="$d['hires30'].' recrue(s) sur 30 jours'" :delay="0" />
        <x-platform.kpi label="Présents aujourd’hui" :value="$p['present']" color="#10b981" :hint="$p['active'] ? round($p['present'] / $p['active'] * 100).' % de l’effectif' : ''" :delay="60" />
        <x-platform.kpi label="En congé" :value="$p['leave']" color="#0ea5e9" hint="congés approuvés en cours" :delay="120" />
        <x-platform.kpi label="En mission" :value="$p['mission']" color="#8b5cf6" hint="missions approuvées en cours" :delay="180" />
        <x-platform.kpi label="À traiter" :value="$d['todo_total']" color="#f59e0b" hint="validations en attente" :delay="240" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="100" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Qui est là aujourd’hui</h3>
            <p class="mb-4 text-xs text-ink-muted">Répartition de l’effectif actif.</p>
            <x-charts.donut :items="[['label' => 'Présents', 'value' => $p['present'], 'color' => '#10b981'], ['label' => 'En congé', 'value' => $p['leave'], 'color' => '#0ea5e9'], ['label' => 'En mission', 'value' => $p['mission'], 'color' => '#8b5cf6']]" :center="$p['active']" center-label="employés" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Embauches des 12 derniers mois</h3>
            <p class="mb-3 text-xs text-ink-muted">Le rythme des arrivées, mois par mois.</p>
            <x-charts.grouped :labels="$d['hires']['labels']" :series="[['name' => '', 'color' => '#6366f1', 'values' => $d['hires']['values']]]" unit="embauche(s)" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.reveal :delay="120" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Effectif par département</h3>
            <p class="mb-4 text-xs text-ink-muted">Les départements les plus importants.</p>
            <x-charts.hbars :items="$d['by_dept']" color="#0ea5e9" unit="employés" />
        </x-ui.reveal>
        <x-ui.reveal :delay="200" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Jours de congé approuvés</h3>
            <p class="mb-3 text-xs text-ink-muted">Sur les 6 derniers mois : repérez les périodes de forte absence.</p>
            <x-charts.trend :labels="$d['leave_days']['labels']" unit="jours" :series="[['name' => 'Jours de congé', 'color' => '#f59e0b', 'values' => $d['leave_days']['values'], 'fill' => true]]" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="140" class="rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Département × ancienneté</h3>
            <p class="mb-3 text-xs text-ink-muted">Tableau croisé : plus la case est foncée, plus l’effectif est important. Il montre où se concentre l’expérience, et où l’équipe est récente.</p>
            <x-charts.matrix corner="Département" :cols="$d['matrix_cols']" :rows="$d['matrix']" />
        </x-ui.reveal>
        <x-ui.reveal :delay="220" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">À traiter</h3>
            <ul class="space-y-2.5">
                @forelse ($d['todo'] as $t)
                    <li wire:key="td-{{ $loop->index }}"><a href="{{ $t['url'] }}" wire:navigate class="flex items-center justify-between gap-3 rounded-xl border border-line px-3 py-2 text-sm transition hover:bg-surface-muted"><span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $tones[$t['tone']] ?? 'bg-slate-400' }}"></span>{{ $t['label'] }}</span><span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs font-semibold">{{ $t['count'] }}</span></a></li>
                @empty
                    <li class="py-8 text-center text-sm text-ink-muted">Rien en attente. 🎉</li>
                @endforelse
            </ul>
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.reveal :delay="100" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Absences à venir (14 jours)</h3>
            <ul class="space-y-2.5 text-sm">
                @forelse ($d['upcoming'] as $u)
                    <li class="flex items-start gap-3" wire:key="up-{{ $loop->index }}"><span class="mt-0.5 w-12 shrink-0 rounded-lg bg-surface-muted px-1 py-1 text-center text-xs font-semibold">{{ $u['date']->format('d/m') }}</span><span class="min-w-0"><span class="font-medium">{{ $u['label'] }}</span><span class="block text-xs text-ink-muted"><span class="font-semibold {{ $u['tone'] === 'violet' ? 'text-violet-600' : 'text-sky-600' }}">{{ $u['kind'] }}</span> · {{ $u['detail'] }}</span></span></li>
                @empty
                    <li class="py-6 text-center text-ink-muted">Aucune absence prévue.</li>
                @endforelse
            </ul>
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Absents aujourd’hui</h3>
            <p class="mb-1 text-xs font-semibold text-sky-600">En congé ({{ $p['leave'] }})</p>
            <p class="mb-3 text-sm text-ink-muted">{{ count($d['on_leave_names']) ? implode(', ', $d['on_leave_names']) : 'Personne.' }}</p>
            <p class="mb-1 text-xs font-semibold text-violet-600">En mission ({{ $p['mission'] }})</p>
            <p class="text-sm text-ink-muted">{{ count($d['on_mission_names']) ? implode(', ', $d['on_mission_names']) : 'Personne.' }}</p>
        </x-ui.reveal>
        <x-ui.reveal :delay="260" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Alertes et anniversaires</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($d['permits'] as $a)<li class="flex gap-2"><span class="text-amber-500">⚠</span><span><span class="font-medium">{{ $a['label'] }}</span><span class="block text-xs text-ink-muted">{{ $a['detail'] }}</span></span></li>@endforeach
                @foreach ($d['birthdays'] as $b)<li class="flex gap-2"><span>{{ $b['today'] ? '🎂' : '🎈' }}</span><span><span class="font-medium">{{ $b['label'] }}</span><span class="block text-xs text-ink-muted">{{ $b['today'] ? 'Anniversaire aujourd’hui !' : 'Anniversaire le '.$b['detail'] }}</span></span></li>@endforeach
                @if (! count($d['permits']) && ! count($d['birthdays']))<li class="py-6 text-center text-ink-muted">Aucune alerte.</li>@endif
            </ul>
        </x-ui.reveal>
    </div>
</div>
