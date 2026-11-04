@php
    $badge = ['trial' => 'bg-sky-100 text-sky-800', 'active' => 'bg-emerald-100 text-emerald-800', 'suspended' => 'bg-rose-100 text-rose-800'];
    $statusLabels = ['trial' => 'Essai', 'active' => 'Actif', 'suspended' => 'Suspendu'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold">Entreprises clientes</h2><p class="text-sm text-ink-muted">Toutes les entreprises hébergées sur Next HR, leur formule et leur effectif.</p></div>
        @if (auth()->user()->platformCan('companies.manage'))
            <a href="{{ route('platform.companies.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvelle entreprise</a>
        @endif
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Rechercher (nom, e-mail)…">
        <select wire:model.live="status" class="input"><option value="">Tous les statuts</option>@foreach ($statusLabels as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select wire:model.live="sector" class="input"><option value="">Tous les secteurs</option>@foreach (config('platform.sectors') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select wire:model.live="plan" class="input"><option value="">Toutes les formules</option>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Entreprise</th><th class="px-4 py-2">Secteur</th><th class="px-4 py-2">Formule</th><th class="px-4 py-2">Effectif</th><th class="px-4 py-2">Échéance</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->companies as $c)
                    @php($sub = $c->current_sub)
                    <tr wire:key="co-{{ $c->id }}">
                        <td class="px-4 py-3 font-medium">{{ $c->name }}<span class="block text-xs font-normal text-ink-muted">{{ config('platform.countries.'.$c->country, $c->country) }} · {{ $c->email }}</span></td>
                        <td class="px-4 py-3">{{ config('platform.sectors.'.$c->sector, '—') }}</td>
                        <td class="px-4 py-3">@if ($sub)<span class="rounded-full px-2 py-0.5 text-xs font-medium text-white" style="background: {{ $sub->plan?->color }}">{{ $sub->plan?->name }}</span><span class="block text-xs text-ink-muted">{{ $sub->statusLabel() }} · {{ config('platform.periods.'.$sub->period_months) }}</span>@else<span class="text-xs text-ink-muted">Sans abonnement</span>@endif</td>
                        <td class="px-4 py-3">{{ $c->active_employees }}@if ($sub?->plan?->max_employees) <span class="text-xs text-ink-muted">/ {{ $sub->plan->max_employees }}</span>@endif</td>
                        <td class="px-4 py-3">{{ $sub?->ends_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $badge[$c->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$c->status] ?? $c->status }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('platform.companies.show', $c->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-muted">Aucune entreprise.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->companies->links() }}
</div>
