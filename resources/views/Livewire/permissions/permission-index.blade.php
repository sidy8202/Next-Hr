@php
    $fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0');
    $styles = ['pending' => 'bg-amber-100 text-amber-900', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800', 'cancelled' => 'bg-slate-100 text-slate-700'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Permissions</h2>
            <p class="text-sm text-ink-muted">Absences autorisées à l’avance (événement familial, raison personnelle, quelques heures). Selon vos droits : vos demandes, celles de votre équipe ou de toute l’entreprise.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->canDo('read', 'leave_settings'))
                <a href="{{ route('permissions.types') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Types et règles</a>
            @endif
            @if (auth()->user()->canDo('create', 'absence_permissions'))
                <a href="{{ route('permissions.create') }}" wire:navigate class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouvelle demande de permission</a>
            @endif
        </div>
    </div>

    <div class="max-w-xs">
        <select wire:model.live="status" class="input">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\LeaveRequest::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Période</th><th class="px-4 py-2">Durée</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->requests as $r)
                    <tr wire:key="p-{{ $r->id }}">
                        <td class="px-4 py-3">{{ $r->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $r->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">{{ $r->type?->name }}</td>
                        <td class="px-4 py-3">{{ $r->periodLabel() }}</td>
                        <td class="px-4 py-3">{{ $fmt($r->days) }} j @if ($r->deducted_days > 0)<span class="block text-xs text-amber-700">dont {{ $fmt($r->deducted_days) }} j du congé</span>@endif</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $styles[$r->status] ?? '' }}">{{ $r->statusLabel() }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('permissions.show', $r->id) }}" wire:navigate class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Détail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucune permission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->requests->links() }}
</div>
