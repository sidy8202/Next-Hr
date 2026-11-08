<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Primes attribuées</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Rubriques propres à un employé (téléphone, prime de performance, indemnité particulière…), avec leur période. Si l'employé a déjà la même rubrique dans son modèle, la valeur attribuée ici prend le dessus.
            </p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="openForm" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Attribuer une prime</button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">Attribuer une prime</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="employeeId">Employé</label>
                    <select id="employeeId" wire:model="employeeId" class="input w-full">
                        <option value="">— Choisir —</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e['id'] }}">{{ $e['label'] }}</option>
                        @endforeach
                    </select>
                    @error('employeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="itemId">Rubrique</label>
                    <select id="itemId" wire:model.live="itemId" class="input w-full">
                        <option value="">— Choisir —</option>
                        @foreach ($items as $i)
                            <option value="{{ $i->id }}">{{ $i->code }} · {{ $i->label }}</option>
                        @endforeach
                    </select>
                    @error('itemId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="amount">Montant (FCFA)</label>
                    <input id="amount" type="text" inputmode="numeric" wire:model="amount" class="input w-full text-right" placeholder="Pour une rubrique à montant">
                    @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="rate">Taux (%)</label>
                    <input id="rate" type="text" inputmode="decimal" wire:model="rate" class="input w-full text-right" placeholder="Pour une rubrique en pourcentage">
                    @error('rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="start_date">Début</label>
                    <input id="start_date" type="date" wire:model="start_date" class="input w-full">
                    @error('start_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="end_date">Fin (vide = sans fin)</label>
                    <input id="end_date" type="date" wire:model="end_date" class="input w-full">
                    @error('end_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="reason">Motif (facultatif)</label>
                    <input id="reason" type="text" wire:model="reason" class="input w-full" placeholder="Ex. Décision de la direction, avenant n° 2">
                    @error('reason') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-ink-muted">Seule la valeur utile selon la rubrique est retenue : un montant pour un montant fixe, un taux pour un pourcentage. Vide = valeur par défaut de la rubrique.</p>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input w-64" placeholder="Employé ou rubrique">
        <select wire:model.live="filterStatus" class="input w-44">
            <option value="current">En cours</option>
            <option value="future">À venir</option>
            <option value="ended">Terminées</option>
            <option value="">Toutes</option>
        </select>
        <span class="text-sm text-ink-muted">{{ $total }} attribution(s)</span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Employé</th>
                    <th class="px-4 py-3">Rubrique</th>
                    <th class="px-4 py-3 text-right">Valeur</th>
                    <th class="px-4 py-3">Période</th>
                    <th class="px-4 py-3">Motif</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($rows as $x)
                    <tr wire:key="epi-{{ $x['row']->id }}">
                        <td class="px-4 py-3 text-ink">{{ $x['name'] }}</td>
                        <td class="px-4 py-3 text-ink">
                            {{ $x['row']->item->label }}
                            <span class="block text-xs text-ink-muted">{{ $x['row']->item->code }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-ink">
                            @if ($x['row']->amount !== null)
                                {{ number_format($x['row']->amount, 0, ',', ' ') }}
                            @elseif ($x['row']->rate !== null)
                                {{ rtrim(rtrim(number_format((float) $x['row']->rate, 3, ',', ''), '0'), ',') }} %
                            @else
                                <span class="text-xs text-ink-muted">valeur par défaut</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-ink">
                            {{ $x['row']->start_date->format('d/m/Y') }} → {{ $x['row']->end_date ? $x['row']->end_date->format('d/m/Y') : 'sans fin' }}
                            @if ($x['status'] === 'future') <span class="ml-1 rounded-full bg-sky-500/15 px-2 py-0.5 text-xs text-sky-700">À venir</span>
                            @elseif ($x['status'] === 'ended') <span class="ml-1 rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Terminée</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $x['row']->reason }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($endingId === $x['row']->id)
                                <input type="date" wire:model="endDate" class="input w-40">
                                <button type="button" wire:click="confirmEnd" class="ml-2 rounded-lg bg-ink px-3 py-1.5 text-sm font-medium text-surface">OK</button>
                                <button type="button" wire:click="cancelEnd" class="ml-1 text-sm text-ink-muted underline">Annuler</button>
                                @error('endDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            @else
                                @if ($canEdit && $x['status'] !== 'ended')
                                    <button type="button" wire:click="askEnd({{ $x['row']->id }})" class="text-sm text-ink underline">Terminer</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" wire:click="deleteRow({{ $x['row']->id }})" wire:confirm="Supprimer cette attribution ?" class="ml-3 text-sm text-red-500">Supprimer</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-ink-muted">Aucune attribution.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($total > 300)
        <p class="text-xs text-ink-muted">Seules les 300 premières lignes sont affichées : affinez la recherche.</p>
    @endif
</div>
