@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0');
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200',
    ];
@endphp

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Demandes de congé</h2>
        <p class="text-sm text-ink-muted">Selon vos droits : vos demandes, celles de votre équipe, ou celles de toute l’entreprise.</p>
    </div>

    <div class="grid gap-3 rounded-2xl border border-line bg-surface p-4 shadow-sm sm:grid-cols-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Employé</label>
            <input type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Nom ou matricule">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Statut</label>
            <select wire:model.live="status" class="input">
                <option value="">Tous</option>
                @foreach (\App\Models\LeaveRequest::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Type</label>
            <select wire:model.live="type" class="input">
                <option value="">Tous</option>
                @foreach ($this->types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Année</label>
            <select wire:model.live="year" class="input">
                <option value="">Toutes</option>
                @for ($y = now()->year + 1; $y >= now()->year - 3; $y--)<option value="{{ $y }}">{{ $y }}</option>@endfor
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Période</th><th class="px-4 py-2">Jours</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->requests as $r)
                    <tr wire:key="r-{{ $r->id }}">
                        <td class="px-4 py-3">{{ $r->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $r->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">{{ $r->type?->name }}@if ($r->exceeds_balance) <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">exception</span>@endif</td>
                        <td class="px-4 py-3">{{ $r->periodLabel() }}</td>
                        <td class="px-4 py-3">{{ $fmt($r->days) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $statusStyles[$r->status] ?? '' }}">{{ $r->statusLabel() }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('leaves.show', $r->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Détail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucune demande ne correspond.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->requests->links() }}
</div>
