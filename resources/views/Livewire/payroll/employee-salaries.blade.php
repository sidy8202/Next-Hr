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

    @if ($canEdit)
        <section x-data="{ open: {{ $withoutNet > 0 ? 'true' : 'false' }} }" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">Nets connus : saisir, importer ou compléter</span>
                <span class="text-xs {{ $withoutNet > 0 ? 'text-amber-600' : 'text-ink-muted' }}">
                    {{ $withoutNet > 0 ? $withoutNet . ' employé(s) sans net connu' : 'Tous les employés ont un net connu' }}
                </span>
            </button>
            <div x-show="open" class="space-y-4 px-5 pb-5">
                <p class="text-sm text-ink-muted">
                    Le net connu est la référence que la paie vérifie avant chaque clôture. Il fait partie de la ligne de salaire datée : pour le changer, créez une nouvelle ligne avec sa date d'effet. Les deux outils ci-dessous ne remplissent que les nets encore vides.
                </p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="netsText">Coller une liste « matricule ; net » (un employé par ligne)</label>
                    <textarea id="netsText" rows="4" wire:model="netsText" class="input w-full font-mono text-xs" placeholder="EMP001;225000&#10;EMP002;200049"></textarea>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" wire:click="importNets" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Importer les nets</button>
                        <button type="button" wire:click="fillMissingNets" wire:confirm="Compléter les nets connus manquants avec le net calculé par le moteur ? À utiliser au démarrage, puis à comparer à vos bulletins existants." class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Compléter les nets manquants d'après le calcul</button>
                    </div>
                </div>
                @if ($netsReport)
                    <ul class="space-y-1 text-sm">
                        @foreach ($netsReport as $line)
                            <li class="{{ $line['status'] === 'set' || $line['status'] === 'same' ? 'text-ink-muted' : 'text-amber-600' }}">
                                <span class="font-medium">{{ $line['matricule'] }}</span> : {{ $line['detail'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
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
                    <th class="px-4 py-3 text-right">Net connu</th>
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
                            <td class="px-4 py-3 text-right text-ink">
                                @if ($r['salary']->mode === 'net_target')
                                    @if (isset($calculated[$r['employee']->id]))
                                        {{ number_format($calculated[$r['employee']->id], 0, ',', ' ') }}
                                        <span class="block text-xs text-sky-700" title="Recalculé à chaque paie pour atteindre le net garanti">calculé</span>
                                    @else
                                        <span class="text-xs text-ink-muted" title="Recalculé à chaque paie pour atteindre le net garanti">à calculer</span>
                                    @endif
                                    @if ($canEdit)
                                        <button type="button" wire:click="freezeSupplement({{ $r['employee']->id }})" class="block text-xs text-ink underline" title="Remplace le net garanti par ce sursalaire fixe, dans une nouvelle ligne datée">Figer</button>
                                    @endif
                                @else
                                    {{ number_format($r['salary']->supplement_amount, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink">
                                {{ $r['salary']->mode === 'net_target' ? 'Net garanti' : 'Brut fixé' }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink">
                                @if ($r['salary']->target_net !== null)
                                    {{ number_format((int) $r['salary']->target_net, 0, ',', ' ') }}
                                @else
                                    <span class="text-xs text-amber-600">à renseigner</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-muted">{{ $r['salary']->effective_from->format('d/m/Y') }}</td>
                        @else
                            <td class="px-4 py-3 text-amber-600" colspan="6">Aucun salaire enregistré</td>
                        @endif
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="showHistory({{ $r['employee']->id }})" class="text-sm text-ink-muted underline">Historique</button>
                            @if ($canEdit)
                                <button type="button" wire:click="openForm({{ $r['employee']->id }})" class="ml-3 rounded-lg border border-line px-3 py-1.5 text-sm text-ink">{{ $r['salary'] ? 'Modifier' : 'Définir' }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-ink-muted">Aucun employé trouvé.</td></tr>
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
                            <option value="gross">Brut fixé : le net est surveillé</option>
                            <option value="net_target">Net garanti : le sursalaire s'ajuste</option>
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

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink" for="target_net">Net connu (FCFA)</label>
                        <div class="flex gap-2">
                            <input id="target_net" type="text" inputmode="numeric" wire:model="target_net" class="input w-full text-right">
                            @if ($mode === 'gross')
                                <button type="button" wire:click="useCalculatedNet" class="whitespace-nowrap rounded-lg border border-line px-3 py-2 text-sm text-ink">Utiliser le net calculé</button>
                            @endif
                        </div>
                        @if ($mode === 'net_target')
                            <p class="mt-1 text-xs text-ink-muted">Le net que l'employé doit toucher : le sursalaire est recalculé à chaque paie pour l'atteindre.</p>
                        @else
                            <p class="mt-1 text-xs text-ink-muted">Le net que l'employé touche normalement (ex. celui de son contrat ou de son dernier bulletin). Chaque paie est vérifiée par rapport à lui avant la clôture. Facultatif, mais recommandé.</p>
                        @endif
                        @error('target_net') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

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
                                <th class="py-2 pr-4 text-right">Net connu</th>
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
                                    <td class="py-2 pr-4 text-ink">{{ $h->mode === 'net_target' ? 'Net garanti' : 'Brut fixé' }}</td>
                                    <td class="py-2 pr-4 text-right text-ink">{{ $h->target_net !== null ? number_format((int) $h->target_net, 0, ',', ' ') : '—' }}</td>
                                    <td class="py-2 text-ink-muted">{{ $h->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-center text-ink-muted">Aucun salaire enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
