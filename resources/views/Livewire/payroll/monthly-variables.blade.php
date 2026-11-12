<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Variables du mois</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Ce qui change chaque mois : heures supplémentaires, jours travaillés, absences non rémunérées, mises à pied, primes et retenues ponctuelles. Les lignes venues de vos absences, de vos mises à pied et de vos jours travaillés arrivent <strong>proposées</strong> : rien n'entre dans la paie avant votre confirmation.
            </p>
        </div>
        @if ($canEdit)
            <div class="flex gap-2">
                <button type="button" wire:click="refreshProposals" class="rounded-lg border border-line bg-surface px-4 py-2 text-sm font-medium text-ink" wire:loading.attr="disabled">Actualiser les propositions</button>
                <button type="button" wire:click="openForm" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Ajouter une variable</button>
            </div>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($closed)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">
            Cette paie est clôturée : ses variables ne se modifient plus. Pour corriger une erreur, utilisez « Régulariser » dans « Paie du mois ».
        </div>
    @endif

    @foreach ($syncNotes as $n)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">{{ $n }}</div>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previousMonth" class="rounded-lg border border-line px-3 py-2 text-sm text-ink" aria-label="Mois précédent">←</button>
            <input type="month" wire:model.live="period" class="input w-44">
            <button type="button" wire:click="nextMonth" class="rounded-lg border border-line px-3 py-2 text-sm text-ink" aria-label="Mois suivant">→</button>
        </div>
        <span class="text-sm font-medium text-ink">{{ $monthLabel }}</span>
        <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Employé">
        <select wire:model.live="statusFilter" class="input w-44">
            <option value="">Tous les états</option>
            <option value="proposed">Proposées</option>
            <option value="confirmed">Confirmées</option>
            <option value="ignored">Ignorées</option>
        </select>
        @if ($canEdit && $proposedCount > 0)
            <button type="button" wire:click="confirmAllProposed" wire:confirm="Confirmer les {{ $proposedCount }} variable(s) proposée(s) de ce mois ?" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">
                Tout confirmer ({{ $proposedCount }})
            </button>
        @endif
    </div>

    @if ($showForm)
        <form wire:submit="addVariable" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">Ajouter une variable pour {{ $monthLabel }}</h2>
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
                    <label class="mb-1 block text-sm font-medium text-ink" for="kind">Nature</label>
                    <select id="kind" wire:model.live="kind" class="input w-full">
                        @foreach ($kinds as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($kind === 'bonus' || $kind === 'deduction')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="payItemId">Rubrique</label>
                        <select id="payItemId" wire:model="payItemId" class="input w-full">
                            <option value="">— Choisir —</option>
                            @foreach (($kind === 'bonus' ? $bonusItems : $deductionItems) as $i)
                                <option value="{{ $i->id }}">{{ $i->code }} · {{ $i->label }}</option>
                            @endforeach
                        </select>
                        @error('payItemId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="amount">Montant (FCFA)</label>
                        <input id="amount" type="text" inputmode="numeric" wire:model="amount" class="input w-full text-right">
                        @error('amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="quantity">{{ $kind === 'overtime_hours' ? 'Nombre d\'heures' : 'Nombre de jours' }}</label>
                        <input id="quantity" type="text" inputmode="decimal" wire:model="quantity" class="input w-full text-right">
                        @error('quantity') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    @if ($kind === 'overtime_hours' || $kind === 'worked_days')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink" for="ratePercent">Majoration</label>
                            <select id="ratePercent" wire:model="ratePercent" class="input w-full">
                                <option value="">Aucune</option>
                                @foreach ($rates as $r)
                                    <option value="{{ rtrim(rtrim(number_format($r->percent, 3, '.', ''), '0'), '.') }}">{{ $r->label }} (+{{ rtrim(rtrim(number_format($r->percent, 3, ',', ''), '0'), ',') }} %)</option>
                                @endforeach
                            </select>
                            @if ($rates->isEmpty()) <p class="mt-1 text-xs text-ink-muted">Aucune majoration définie : ajoutez-les dans « Réglages des variables ».</p> @endif
                            @error('ratePercent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($kind === 'suspension')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink" for="daysMode">Décompte des jours</label>
                            <select id="daysMode" wire:model="daysMode" class="input w-full">
                                <option value="working">Jours ouvrables</option>
                                <option value="calendar">Jours calendaires</option>
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="amount2">Montant imposé (facultatif)</label>
                        <input id="amount2" type="text" inputmode="numeric" wire:model="amount" class="input w-full text-right" placeholder="Vide = calculé automatiquement">
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="note">Note (facultatif)</label>
                    <input id="note" type="text" wire:model="note" class="input w-full">
                    @error('note') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Ajouter</button>
            </div>
        </form>
    @endif

    @forelse ($groups as $g)
        <div class="overflow-hidden rounded-xl border border-line bg-surface" wire:key="grp-{{ $g['name'] }}">
            <div class="flex items-center justify-between bg-surface-muted px-4 py-3">
                <span class="text-sm font-semibold text-ink">{{ $g['name'] }}</span>
                @if ($g['noSalary']) <span class="text-xs text-amber-600">Aucun salaire en vigueur ce mois : montants non calculables</span> @endif
            </div>
            <div class="divide-y divide-line">
                @foreach ($g['rows'] as $r)
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 {{ $r['v']->status === 'ignored' ? 'opacity-60' : '' }}" wire:key="var-{{ $r['v']->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-ink">{{ $r['label'] }}</span>
                                @if ($r['v']->status === 'proposed') <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">Proposée</span>
                                @elseif ($r['v']->status === 'confirmed') <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Confirmée</span>
                                @else <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Ignorée</span>
                                @endif
                                @if ($r['v']->kind === 'suspension' && $r['v']->days_mode) <span class="text-xs text-ink-muted">{{ $r['v']->days_mode === 'calendar' ? 'jours calendaires' : 'jours ouvrables' }}</span> @endif
                            </div>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $r['v']->source_ref ?? ($r['v']->isAuto() ? '' : 'Saisie manuelle') }}{{ $r['v']->note ? ' · ' . $r['v']->note : '' }}</p>
                        </div>

                        @if ($r['v']->quantity !== null)
                            <div class="flex items-center gap-1">
                                <input type="text" inputmode="decimal" value="{{ rtrim(rtrim(number_format($r['v']->quantity, 2, ',', ''), '0'), ',') }}"
                                    wire:change="saveQuantity({{ $r['v']->id }}, $event.target.value)" class="input w-20 text-right" @disabled(! $canEdit)>
                                <span class="text-xs text-ink-muted">{{ $r['v']->unit === 'hours' ? 'h' : 'j' }}</span>
                            </div>
                        @endif

                        @if ($r['v']->kind === 'overtime_hours' || $r['v']->kind === 'worked_days')
                            <select wire:change="saveRate({{ $r['v']->id }}, $event.target.value)" class="input w-40" @disabled(! $canEdit)>
                                <option value="">Sans majoration</option>
                                @foreach ($rates as $rt)
                                    <option value="{{ rtrim(rtrim(number_format($rt->percent, 3, '.', ''), '0'), '.') }}" @selected($r['v']->rate_percent !== null && abs($r['v']->rate_percent - $rt->percent) < 0.0005)>+{{ rtrim(rtrim(number_format($rt->percent, 3, ',', ''), '0'), ',') }} %</option>
                                @endforeach
                            </select>
                        @endif

                        <div class="w-44 text-right">
                            <input type="text" inputmode="numeric" value="{{ $r['v']->amount_override !== null ? $r['v']->amount_override : '' }}"
                                placeholder="{{ $r['amount'] !== null ? number_format($r['amount'], 0, ',', ' ') : 'Montant' }}"
                                wire:change="saveAmount({{ $r['v']->id }}, $event.target.value)" class="input w-full text-right" @disabled(! $canEdit)>
                            <span class="text-xs text-ink-muted">
                                @if ($r['effect'] === 'gross_add') s'ajoute au brut
                                @elseif ($r['effect'] === 'gross_deduct') réduit le brut
                                @else retenue sur le net
                                @endif
                            </span>
                        </div>

                        @if ($canEdit || $canDelete)
                            <div class="flex items-center gap-3 text-sm whitespace-nowrap">
                                @if ($canEdit && $r['v']->status === 'proposed')
                                    <button type="button" wire:click="confirm({{ $r['v']->id }})" class="rounded-lg bg-ink px-3 py-1.5 font-medium text-surface">Confirmer</button>
                                    <button type="button" wire:click="ignore({{ $r['v']->id }})" class="text-ink-muted underline">Ignorer</button>
                                @elseif ($canEdit && $r['v']->status === 'confirmed')
                                    <button type="button" wire:click="ignore({{ $r['v']->id }})" class="text-ink-muted underline">Ignorer</button>
                                @elseif ($canEdit && $r['v']->status === 'ignored' && $r['v']->isAuto())
                                    <button type="button" wire:click="reopen({{ $r['v']->id }})" class="text-ink underline">Reproposer</button>
                                @endif
                                @if ($canDelete && ! $r['v']->isAuto())
                                    <button type="button" wire:click="deleteVariable({{ $r['v']->id }})" wire:confirm="Supprimer cette variable ?" class="text-red-500">Supprimer</button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="rounded-xl border border-line bg-surface p-6 text-center text-sm text-ink-muted">
            Aucune variable pour {{ $monthLabel }}. Cliquez sur « Actualiser les propositions » pour récupérer vos absences, mises à pied et jours travaillés, ou ajoutez une variable à la main.
        </p>
    @endforelse

    <div class="overflow-hidden rounded-xl border border-line bg-surface">
        <div class="bg-surface-muted px-4 py-3">
            <span class="text-sm font-semibold text-ink">Échéances de prêts et d'avances de {{ $monthLabel }}</span>
            <span class="ml-2 text-xs text-ink-muted">Elles se prélèvent automatiquement tant que le prêt est « en cours ». Pour en gérer une, ouvrez « Prêts et avances ».</span>
        </div>
        <div class="divide-y divide-line">
            @forelse ($installments as $x)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3" wire:key="inst-{{ $x['i']->id }}">
                    <div>
                        <span class="text-sm font-medium text-ink">{{ $x['name'] }}</span>
                        <span class="ml-2 text-xs text-ink-muted">{{ $x['i']->loan->type === 'advance' ? 'Avance' : 'Prêt' }}{{ $x['i']->loan->label ? ' · ' . $x['i']->loan->label : '' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-ink">{{ number_format($x['i']->amount, 0, ',', ' ') }}</span>
                        @if ($x['i']->status === 'suspended' || $x['i']->loan->status === 'suspended')
                            <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">Suspendue</span>
                        @else
                            <span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-xs text-sky-700">À prélever</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-4 py-4 text-sm text-ink-muted">Aucune échéance ce mois-ci.</p>
            @endforelse
        </div>
    </div>
</div>
