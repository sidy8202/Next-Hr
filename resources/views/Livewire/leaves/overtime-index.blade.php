@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Jours de repos travaillés</h2>
        <p class="text-sm text-ink-muted">
            Vous avez travaillé un jour de repos ou férié (un dimanche, par exemple) ? Déclarez-le : après validation du chef puis de la RH,
            vous recevez soit un jour de récupération à prendre avant une date limite, soit un ajout direct à votre solde de congé.
        </p>
    </div>

    @error('overtime')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if (auth()->user()->canDo('create', 'overtime'))
        <form wire:submit="save" class="grid gap-3 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Employé</label>
                <select wire:model="employee_id" class="input">
                    <option value="">Choisir…</option>
                    @foreach ($this->declarable as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Jour travaillé</label>
                <input type="date" max="{{ today()->toDateString() }}" wire:model="worked_on" class="input">
                @error('worked_on')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durée (jour)</label>
                <select wire:model="days" class="input">
                    <option value="0.25">¼ de jour</option><option value="0.5">½ jour</option><option value="0.75">¾ de jour</option><option value="1">1 jour</option>
                </select>
                @error('days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-sm font-medium">Raison du travail ce jour-là</label>
                <input type="text" wire:model="reason" class="input">
            </div>
            <div class="flex items-end justify-end">
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Déclarer</button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Jour travaillé</th><th class="px-4 py-2">Durée</th><th class="px-4 py-2">Statut</th><th class="px-4 py-2">Compensation</th><th class="px-4 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->records as $r)
                    <tr wire:key="ot-{{ $r->id }}">
                        <td class="px-4 py-3">{{ $r->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $r->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">{{ $r->worked_on->translatedFormat('l d/m/Y') }}<span class="block text-xs text-ink-muted">{{ $r->reason }}</span></td>
                        <td class="px-4 py-3">{{ $fmt($r->days) }} j</td>
                        <td class="px-4 py-3">{{ $r->statusLabel() }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($r->compensation)
                                {{ \App\Models\OvertimeRecord::COMPENSATIONS[$r->compensation] }} : {{ $fmt($r->credited_days) }} j
                                @if ($r->expires_on)<span class="block text-ink-muted">à prendre avant le {{ $r->expires_on->format('d/m/Y') }}</span>@endif
                            @else — @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($r->status === 'pending')
                                <button type="button" wire:click="cancel({{ $r->id }})" wire:confirm="Annuler cette déclaration ?" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Annuler</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Aucune déclaration.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->records->links() }}
</div>
