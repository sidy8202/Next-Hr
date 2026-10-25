@php($fmt = fn ($n) => (rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') ?: '0'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Soldes de congé</h2>
        <p class="text-sm text-ink-muted">
            Cadence mensuelle (par défaut {{ $fmt(\App\Models\LeaveSetting::for(auth()->user()->company_id)->default_monthly_days) }} j/mois), solde initial des employés existants et corrections.
            @if ($this->canEdit) Chaque ajustement est enregistré avec son motif et notifié à l’employé. @else Consultation selon votre portée (vous, votre équipe ou tous les employés). @endif
        </p>
    </div>

    @error('adjust')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">{{ $message }}</p>
    @enderror

    <div class="max-w-sm">
        <input type="search" wire:model.live.debounce.300ms="search" class="input" placeholder="Nom ou matricule">
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted">
                <tr>
                    <th class="px-4 py-2">Employé</th><th class="px-4 py-2">Cadence (j/mois)</th><th class="px-4 py-2">Planning</th><th class="px-4 py-2">Solde</th>
                    <th class="px-4 py-2">En attente</th><th class="px-4 py-2">Récupération</th><th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($this->employees as $e)
                    <tr wire:key="bal-{{ $e->id }}">
                        <td class="px-4 py-3">{{ $e->full_name }}<span class="block text-xs text-ink-muted">{{ $e->matricule }} · {{ $e->department?->name }}</span></td>
                        <td class="px-4 py-3">
                            @if ($this->canEdit)
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.25" min="0" max="10" wire:model="rates.{{ $e->id }}" placeholder="{{ $fmt($e->rate) }}" class="input w-20 py-1">
                                    <button type="button" wire:click="saveRate({{ $e->id }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">OK</button>
                                </div>
                                @error("rates.{$e->id}")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            @else
                                {{ $fmt($e->rate) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if ($this->canEdit)
                                <select wire:change="assignSchedule({{ $e->id }}, $event.target.value)" class="input w-40 py-1 text-xs">
                                    <option value="">Semaine standard</option>
                                    @foreach ($this->schedules as $sc)<option value="{{ $sc->id }}" @selected($e->work_schedule_id === $sc->id)>{{ $sc->name }}</option>@endforeach
                                </select>
                            @else
                                {{ $this->schedules->firstWhere('id', $e->work_schedule_id)?->name ?? 'Semaine standard' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium {{ $e->balance < 0 ? 'text-rose-600' : '' }}">{{ $fmt($e->balance) }} j</td>
                        <td class="px-4 py-3">{{ $fmt($e->reserved) }} j</td>
                        <td class="px-4 py-3">{{ $fmt($e->recovery) }} j</td>
                        <td class="px-4 py-3 text-right">@if ($this->canEdit)<button type="button" wire:click="startAdjust({{ $e->id }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Ajuster le solde</button>@endif</td>
                    </tr>
                    @if ($this->canEdit && $adjusting === $e->id)
                        <tr wire:key="adj-{{ $e->id }}">
                            <td colspan="7" class="bg-surface-muted px-4 py-3">
                                <form wire:submit="adjust" class="grid gap-3 sm:grid-cols-5">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium">Solde à ajuster</label>
                                        <select wire:model="pool" class="input py-1"><option value="annual">Congé</option><option value="recovery">Récupération</option></select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium">Jours (+ ou −)</label>
                                        <input type="number" step="0.25" wire:model="adjustDays" class="input py-1" placeholder="ex. 12 ou -2">
                                        @error('adjustDays')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium">Motif (ex. solde initial à l’installation)</label>
                                        <input type="text" wire:model="adjustNote" class="input py-1">
                                        @error('adjustNote')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <button type="button" wire:click="stopAdjust" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Annuler</button>
                                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $this->employees->links() }}
</div>
