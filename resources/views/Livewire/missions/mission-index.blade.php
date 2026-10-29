@php
    $styles = ['draft' => 'bg-slate-100 text-slate-700', 'submitted' => 'bg-amber-100 text-amber-900', 'returned' => 'bg-rose-100 text-rose-800', 'hr_approved' => 'bg-sky-100 text-sky-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800', 'cancelled' => 'bg-slate-100 text-slate-700'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Ordres de mission</h2>
            <p class="text-sm text-ink-muted">Chef de département → vérification RH → approbation DG. Chaque participant reçoit son propre ordre.</p>
        </div>
        @if (auth()->user()->canDo('create', 'mission_orders'))
            <a href="{{ route('missions.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvel ordre de mission</a>
        @endif
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif

    <div class="max-w-xs">
        <select wire:model.live="status" class="input">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Réf.</th><th class="px-4 py-2">Objet</th><th class="px-4 py-2">Destination</th><th class="px-4 py-2">Dates</th><th class="px-4 py-2">Participants</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->missions as $m)
                    <tr wire:key="mi-{{ $m->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $m->reference ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $m->purpose }}<span class="block text-xs text-ink-muted">{{ $m->department?->name }} · {{ $m->requester?->name }}</span></td>
                        <td class="px-4 py-3">{{ $m->destination_zone }}</td>
                        <td class="px-4 py-3">{{ $m->start_date->format('d/m/Y') }} → {{ $m->end_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $m->participants_count }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $styles[$m->status->value] ?? '' }}">{{ $m->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('missions.show', $m->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-muted">Aucun ordre de mission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->missions->links() }}
</div>
