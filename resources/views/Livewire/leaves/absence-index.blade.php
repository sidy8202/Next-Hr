@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',') ?: '0'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Absences</h2>
        <p class="text-sm text-ink-muted">Une absence non justifiée est retirée du solde de congé de l’employé (si le paramètre est actif). Si elle est justifiée ensuite, les jours sont rendus. L’employé est prévenu.</p>
    </div>

    @error('absence')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    @if (auth()->user()->canDo('create', 'absences'))
        <form wire:submit="save" class="grid gap-3 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Employé</label>
                <select wire:model="employee_id" class="input">
                    <option value="">Choisir…</option>
                    @foreach ($this->employees as $e)<option value="{{ $e->id }}">{{ $e->last_name }} {{ $e->first_name }} — {{ $e->matricule }}</option>@endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Du</label>
                <input type="date" wire:model.live="start_date" class="input">
                @error('start_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Au (inclus)</label>
                <input type="date" wire:model="end_date" class="input">
                @error('end_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Motif ou circonstances</label>
                <input type="text" wire:model="reason" class="input">
            </div>
            <label class="flex items-center gap-2 pt-6 text-sm"><input type="checkbox" wire:model="justified"> Absence justifiée</label>
            <div class="flex items-end justify-end">
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer l’absence</button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr><th class="px-4 py-2">Employé</th><th class="px-4 py-2">Période</th><th class="px-4 py-2">Jours</th><th class="px-4 py-2">Situation</th><th class="px-4 py-2 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($this->absences as $a)
                    <tr wire:key="abs-{{ $a->id }}">
                        <td class="px-4 py-3">{{ $a->employee?->full_name }}<span class="block text-xs text-ink-muted">{{ $a->employee?->matricule }}</span></td>
                        <td class="px-4 py-3">{{ $a->start_date->equalTo($a->end_date) ? $a->start_date->format('d/m/Y') : $a->start_date->format('d/m/Y').' → '.$a->end_date->format('d/m/Y') }}<span class="block text-xs text-ink-muted">{{ $a->reason }}</span></td>
                        <td class="px-4 py-3">{{ $fmt($a->days) }}</td>
                        <td class="px-4 py-3">
                            @if ($a->justified)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">Justifiée</span>
                            @else<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-800 dark:bg-rose-500/20 dark:text-rose-200">Non justifiée @if ($a->deducted_days > 0) · −{{ $fmt($a->deducted_days) }} j du solde @endif</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2 text-xs">
                                @if (! $a->justified && auth()->user()->canDo('update', 'absences'))
                                    <button type="button" wire:click="justify({{ $a->id }})" wire:confirm="Justifier cette absence ? Les jours retirés seront rendus." class="rounded border border-line px-2 py-1 hover:bg-surface-muted">Justifier</button>
                                @endif
                                @if (auth()->user()->canDo('delete', 'absences'))
                                    <button type="button" wire:click="delete({{ $a->id }})" wire:confirm="Supprimer cette absence ?" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Supprimer</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-ink-muted">Aucune absence enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->absences->links() }}
</div>
