<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Salaires des employés</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Chaque changement crée une nouvelle ligne datée : l'historique n'est jamais écrasé.
            </p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="initFromContracts" wire:confirm="Créer un salaire pour chaque employé qui n'en a pas encore, à partir de son contrat ?" class="rounded-lg border border-line bg-surface px-4 py-2 text-sm font-medium text-ink">
                Initialiser depuis les contrats
            </button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input w-72" placeholder="Rechercher un nom ou un matricule">
        <span class="text-sm text-ink-muted">
            {{ $total }} employé(s)
            @if ($withoutSalary > 0) · <span class="text-amber-600">{{ $withoutSalary }} sans salaire enregistré</span> @endif
        </span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Employé</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3 text-right">Salaire de base</th>
                    <th class="px-4 py-3 text-right">Sursalaire</th>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3">Depuis</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($rows as $r)
                    <tr>
                        <td class="px-4 py-3 text-ink">
                            {{ $r['name'] }}
                            <span class="block text-xs text-ink-muted">{{ $r['employee']->matricule }}</span>
                        </td>
                        @if ($r['salary'])
                            <td class="px-4 py-3 text-ink">{{ $r['salary']->grade?->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-ink">{{ number_format($r['salary']->base_amount, 0, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-ink">{{ number_format($r['salary']->supplement_amount, 0, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-ink">
                                @if ($r['salary']->mode === 'net_target')
                                    Net garanti <span class="text-ink-muted">({{ number_format((int) $r['salary']->target_net, 0, ',', ' ') }})</span>
                                @else
                                    Brut
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-muted">{{ $r['salary']->effective_from->format('d/m/Y') }}</td>
                        @else
                            <td class="px-4 py-3 text-amber-600" colspan="5">Aucun salaire enregistré</td>
                        @endif
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="showHistory({{ $r['employee']->id }})" class="text-sm text-ink-muted underline">Historique</button>
                            @if ($canEdit)
                                <button type="button" wire:click="openForm({{ $r['employee']->id }})" class="ml-3 rounded-lg border border-line px-3 py-1.5 text-sm text-ink">{{ $r['salary'] ? 'Modifier' : 'Définir' }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-muted">Aucun employé trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($total > 300)
        <p class="text-xs text-ink-muted">Seuls les 300 premiers résultats sont affichés : affinez la recherche.</p>
    @endif

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="salary-form">
            <form wire:submit="save" class="max-h-[90vh] w-full max-w-2xl space-y-4 overflow-y-auto rounded-xl border border-line bg-surface p-6">
                <div>
                    <h2 class="text-base font-semibold text-ink">Nouveau salaire : {{ $employeeLabel }}</h2>
                    <p class="text-xs text-ink-muted">Le salaire actuel reste dans l'historique et prend fin la veille de la date d'effet.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="effective_from">Date d'effet</label>
                        <input id="effective_from" type="date" wire:model="effective_from" class="input w-full">
                        @error('effective_from') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="mode">Mode de salaire</label>
                        <select id="mode" wire:model.live="mode" class="input w-full">
                            <option value="gross">Brut (salaire de base + sursalaire)</option>
                            <option value="net_target">Net garanti (net convenu)</option>
                        </select>
                        @error('mode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="grade">Catégorie</label>
                        <select id="grade" wire:model.live="salary_grade_id" class="input w-full">
                            <option value="">— Aucune —</option>
                            @foreach ($grades as $g)
                                <option value="{{ $g->id }}">{{ $g->code }}{{ $g->label ? ' · ' . $g->label : '' }} ({{ $g->grid->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="step">Échelon</label>
                        <select id="step" wire:model.live="salary_step_id" class="input w-full">
                            <option value="">— Aucun —</option>
                            @foreach ($grades as $g)
                                @if ((string) $salary_grade_id === '' || (string) $salary_grade_id === (string) $g->id)
                                    @foreach ($g->steps as $s)
                                        <option value="{{ $s->id }}">{{ $g->code }} · {{ $s->label }} — {{ number_format($s->amount, 0, ',', ' ') }}</option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-muted">Choisir un échelon remplit le salaire de base ; vous pouvez ensuite l'ajuster.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="base_amount">Salaire de base (FCFA)</label>
                        <input id="base_amount" type="text" inputmode="numeric" wire:model="base_amount" class="input w-full text-right">
                        @error('base_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="supplement_amount">Sursalaire (FCFA)</label>
                        <input id="supplement_amount" type="text" inputmode="numeric" wire:model="supplement_amount" class="input w-full text-right">
                        @error('supplement_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    @if ($mode === 'net_target')
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-ink" for="target_net">Net convenu (FCFA)</label>
                            <input id="target_net" type="text" inputmode="numeric" wire:model="target_net" class="input w-full text-right">
                            <p class="mt-1 text-xs text-ink-muted">Le sursalaire sera recalculé automatiquement par le moteur de paie pour atteindre ce net (prévu au lot 13d).</p>
                            @error('target_net') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink" for="notes">Note (facultatif)</label>
                        <textarea id="notes" rows="2" wire:model="notes" class="input w-full" placeholder="Ex. Promotion, révision annuelle, avenant au contrat"></textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                    <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface" wire:loading.attr="disabled">Enregistrer</button>
                </div>
            </form>
        </div>
    @endif

    @if ($historyId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="salary-history">
            <div class="max-h-[90vh] w-full max-w-3xl space-y-4 overflow-y-auto rounded-xl border border-line bg-surface p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-semibold text-ink">Historique des salaires : {{ $historyName }}</h2>
                    <button type="button" wire:click="closeHistory" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Fermer</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="py-2 pr-4">Période</th>
                                <th class="py-2 pr-4">Catégorie</th>
                                <th class="py-2 pr-4 text-right">Base</th>
                                <th class="py-2 pr-4 text-right">Sursalaire</th>
                                <th class="py-2 pr-4">Mode</th>
                                <th class="py-2">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($history as $h)
                                <tr>
                                    <td class="py-2 pr-4 text-ink">
                                        {{ $h->effective_from->format('d/m/Y') }}
                                        → {{ $h->effective_until ? $h->effective_until->format('d/m/Y') : 'en cours' }}
                                    </td>
                                    <td class="py-2 pr-4 text-ink">{{ $h->grade?->code ?? '—' }}{{ $h->step ? ' · ' . $h->step->label : '' }}</td>
                                    <td class="py-2 pr-4 text-right text-ink">{{ number_format($h->base_amount, 0, ',', ' ') }}</td>
                                    <td class="py-2 pr-4 text-right text-ink">{{ number_format($h->supplement_amount, 0, ',', ' ') }}</td>
                                    <td class="py-2 pr-4 text-ink">{{ $h->mode === 'net_target' ? 'Net ' . number_format((int) $h->target_net, 0, ',', ' ') : 'Brut' }}</td>
                                    <td class="py-2 text-ink-muted">{{ $h->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center text-ink-muted">Aucun salaire enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
