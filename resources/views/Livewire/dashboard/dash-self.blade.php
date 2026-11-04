@php
    $badge = ['pending' => ['En attente', 'bg-amber-100 text-amber-900'], 'approved' => ['Approuvé', 'bg-emerald-100 text-emerald-800'], 'rejected' => ['Refusé', 'bg-rose-100 text-rose-800']];
    $e = $d['employee'];
@endphp
<div class="space-y-6">
    <x-ui.reveal class="rounded-2xl border border-line bg-gradient-to-br from-indigo-50 to-violet-50 p-5 shadow-sm dark:from-indigo-500/10 dark:to-violet-500/10">
        <p class="text-lg font-semibold">{{ $e?->full_name }}</p>
        <p class="text-sm text-ink-muted">{{ $e?->position?->title }}@if ($e?->department) · {{ $e->department->name }}@endif @if ($d['tenure_years'] !== null) · {{ $d['tenure_years'] }} an(s) d’ancienneté @endif</p>
        @if ($d['on_mission_now'])<p class="mt-2 inline-block rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800">Vous êtes actuellement en mission : {{ $d['next_mission']->destination_zone }}</p>@endif
    </x-ui.reveal>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-platform.kpi label="Mes demandes en attente" :value="$d['pending']" color="#f59e0b" hint="congés en cours de validation" :delay="0" />
        <x-ui.reveal :delay="70" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-ink-muted">Mon prochain congé</p>
            @if ($d['next_leave'])<p class="mt-1 text-xl font-bold">{{ $d['next_leave']->start_date->format('d/m/Y') }}</p><p class="text-xs text-ink-muted">jusqu’au {{ $d['next_leave']->end_date->format('d/m/Y') }} · {{ (float) $d['next_leave']->days }} jour(s)</p>
            @else<p class="mt-1 text-xl font-bold text-ink-muted">Aucun</p><p class="text-xs text-ink-muted">Aucun congé approuvé à venir</p>@endif
        </x-ui.reveal>
        <x-ui.reveal :delay="140" class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-ink-muted">Ma prochaine mission</p>
            @if ($d['next_mission'])<p class="mt-1 text-xl font-bold">{{ $d['next_mission']->destination_zone }}</p><p class="text-xs text-ink-muted">du {{ $d['next_mission']->start_date->format('d/m') }} au {{ $d['next_mission']->end_date->format('d/m/Y') }}</p>
            @else<p class="mt-1 text-xl font-bold text-ink-muted">Aucune</p><p class="text-xs text-ink-muted">Aucune mission approuvée à venir</p>@endif
        </x-ui.reveal>
        <x-ui.reveal :delay="210" class="rounded-2xl border border-dashed border-line bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-ink-muted">Mes bulletins de paie</p>
            <p class="mt-1 text-xl font-bold text-ink-muted">Bientôt</p>
            <p class="text-xs text-ink-muted">Retrouvez ici vos bulletins dès l’ouverture du module de paie.</p>
        </x-ui.reveal>
    </div>

    <x-ui.reveal :delay="150" class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Mes dernières demandes de congé</h3>
        <ul class="divide-y divide-line text-sm">
            @forelse ($d['mine'] as $l)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="my-{{ $l->id }}"><span>Du {{ $l->start_date->format('d/m/Y') }} au {{ $l->end_date->format('d/m/Y') }} <span class="text-xs text-ink-muted">· {{ (float) $l->days }} jour(s)</span></span><span class="rounded-full px-2 py-0.5 text-xs {{ ($badge[$l->status][1] ?? 'bg-slate-100 text-slate-700') }}">{{ $badge[$l->status][0] ?? $l->status }}</span></li>
            @empty
                <li class="py-6 text-center text-ink-muted">Aucune demande pour le moment.</li>
            @endforelse
        </ul>
    </x-ui.reveal>
</div>
