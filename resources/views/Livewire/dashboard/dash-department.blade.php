@php
    $p = $d['presence'];
@endphp
<div class="space-y-6">
    <x-ui.reveal><h3 class="text-lg font-semibold">{{ $d['name'] }}</h3></x-ui.reveal>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-platform.kpi label="Effectif du département" :value="$p['active']" suffix=" employés" color="#6366f1" :delay="0" />
        <x-platform.kpi label="Présents aujourd’hui" :value="$p['present']" color="#10b981" :delay="60" />
        <x-platform.kpi label="En congé" :value="$p['leave']" color="#0ea5e9" :delay="120" />
        <x-platform.kpi label="En mission" :value="$p['mission']" color="#8b5cf6" :delay="180" />
        @if ($d['pending'] !== null)<x-platform.kpi label="Demandes à valider" :value="$d['pending']" color="#f59e0b" hint="congés de votre équipe" :delay="240" />@endif
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.reveal :delay="100" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold">Disponibilité de l’équipe</h3>
            <x-charts.donut :items="[['label' => 'Présents', 'value' => $p['present'], 'color' => '#10b981'], ['label' => 'En congé', 'value' => $p['leave'], 'color' => '#0ea5e9'], ['label' => 'En mission', 'value' => $p['mission'], 'color' => '#8b5cf6']]" :center="$p['active']" center-label="employés" />
        </x-ui.reveal>
        <x-ui.reveal :delay="180" class="rounded-2xl border border-line bg-surface p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold">Jours de congé du département</h3>
            <p class="mb-3 text-xs text-ink-muted">Sur les 6 derniers mois.</p>
            <x-charts.trend :labels="$d['leave_days']['labels']" unit="jours" :series="[['name' => 'Jours de congé', 'color' => '#f59e0b', 'values' => $d['leave_days']['values'], 'fill' => true]]" />
        </x-ui.reveal>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.reveal :delay="120" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Absences à venir (14 jours)</h3>
            <ul class="space-y-2.5 text-sm">
                @forelse ($d['upcoming'] as $u)
                    <li class="flex items-start gap-3" wire:key="du-{{ $loop->index }}"><span class="mt-0.5 w-12 shrink-0 rounded-lg bg-surface-muted px-1 py-1 text-center text-xs font-semibold">{{ $u['date']->format('d/m') }}</span><span class="min-w-0"><span class="font-medium">{{ $u['label'] }}</span><span class="block text-xs text-ink-muted"><span class="font-semibold {{ $u['tone'] === 'violet' ? 'text-violet-600' : 'text-sky-600' }}">{{ $u['kind'] }}</span> · {{ $u['detail'] }}</span></span></li>
                @empty
                    <li class="py-6 text-center text-ink-muted">Aucune absence prévue.</li>
                @endforelse
            </ul>
        </x-ui.reveal>
        <x-ui.reveal :delay="200" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Absents aujourd’hui</h3>
            @if ($d['show_names'])
                <p class="mb-1 text-xs font-semibold text-sky-600">En congé ({{ $p['leave'] }})</p>
                <p class="mb-3 text-sm text-ink-muted">{{ count($d['on_leave_names']) ? implode(', ', $d['on_leave_names']) : 'Personne.' }}</p>
                <p class="mb-1 text-xs font-semibold text-violet-600">En mission ({{ $p['mission'] }})</p>
                <p class="text-sm text-ink-muted">{{ count($d['on_mission_names']) ? implode(', ', $d['on_mission_names']) : 'Personne.' }}</p>
            @else
                <p class="text-sm text-ink-muted">{{ $p['leave'] }} collègue(s) en congé et {{ $p['mission'] }} en mission aujourd’hui.</p>
            @endif
        </x-ui.reveal>
    </div>
</div>
