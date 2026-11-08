<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Prêts et avances</h1>
            <p class="mt-1 text-sm text-ink-muted">
                On enregistre le <strong>montant de l'échéance mensuelle</strong>. Le taux d'effort (échéance ÷ salaire) est indicatif. Le solde ne baisse qu'à la validation de la paie, jamais à un simple calcul.
            </p>
        </div>
        @if ($canCreate)
            <button type="button" wire:click="openForm" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouvelle demande</button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">Nouvelle demande de prêt ou d'avance</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="employeeId">Employé</label>
                    <select id="employeeId" wire:model.live="employeeId" class="input w-full">
                        <option value="">— Choisir —</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e['id'] }}">{{ $e['label'] }}</option>
                        @endforeach
                    </select>
                    @error('employeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="type">Nature</label>
                    <select id="type" wire:model="type" class="input w-full">
                        @foreach ($types as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="principal">Montant accordé (FCFA)</label>
                    <input id="principal" type="text" inputmode="numeric" wire:model.live.debounce.400ms="principal" class="input w-full text-right">
                    @error('principal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="installment">Échéance mensuelle (FCFA)</label>
                    <input id="installment" type="text" inputmode="numeric" wire:model.live.debounce.400ms="installment" class="input w-full text-right">
                    @error('installment') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="first_month">Première échéance</label>
                    <input id="first_month" type="month" wire:model.live="first_month" class="input w-full">
                    @error('first_month') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="interest_rate">Taux d'intérêt % (indicatif, facultatif)</label>
                    <input id="interest_rate" type="text" inputmode="decimal" wire:model="interest_rate" class="input w-full text-right">
                    @error('interest_rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="label">Objet (facultatif)</label>
                    <input id="label" type="text" wire:model="label" class="input w-full" placeholder="Ex. Frais de scolarité, urgence familiale">
                    @error('label') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="notes">Notes (facultatif)</label>
                    <input id="notes" type="text" wire:model="notes" class="input w-full">
                </div>
            </div>

            @if ($formCount > 0)
                <p class="rounded-lg bg-surface-muted px-3 py-2 text-sm text-ink">
                    {{ $formCount }} échéance(s) prévue(s)
                    @if ($formEffort !== null) · taux d'effort indicatif : <strong>{{ str_replace('.', ',', (string) $formEffort) }} %</strong> du salaire de référence @endif
                    @if ($formEffort === null && $employeeId !== '') · <span class="text-amber-600">aucun salaire en vigueur : taux d'effort non calculable</span> @endif
                </p>
            @endif

            @if ($formEffort !== null && $threshold !== null && $formEffort > (float) $threshold)
                <label class="flex items-start gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-700">
                    <input type="checkbox" wire:model="acknowledge" class="mt-0.5">
                    <span>Le taux d'effort dépasse le seuil d'alerte de {{ str_replace('.', ',', (string) $threshold) }} %. Je confirme la demande en connaissance de cause.</span>
                </label>
            @endif
            @error('acknowledge') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer la demande</button>
            </div>
        </form>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Employé">
        <select wire:model.live="filterStatus" class="input w-48">
            <option value="">Tous les états</option>
            @foreach ($statuses as $key => $text)
                <option value="{{ $key }}">{{ $text }}</option>
            @endforeach
        </select>
        <span class="text-sm text-ink-muted">{{ $loans->count() }} dossier(s)</span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Employé</th>
                    <th class="px-4 py-3">Nature</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-right">Échéance</th>
                    <th class="px-4 py-3 text-right">Solde dû</th>
                    <th class="px-4 py-3">État</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($loans as $l)
                    <tr wire:key="loan-{{ $l->id }}" class="{{ $selected && $selected->id === $l->id ? 'bg-surface-muted' : '' }}">
                        <td class="px-4 py-3 text-ink">{{ $names[$l->employee_id] ?? ('#' . $l->employee_id) }}</td>
                        <td class="px-4 py-3 text-ink">{{ $types[$l->type] ?? $l->type }}<span class="block text-xs text-ink-muted">{{ $l->label }}</span></td>
                        <td class="px-4 py-3 text-right text-ink">{{ number_format($l->principal, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-ink">{{ number_format($l->installment_amount, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-ink">{{ number_format((int) ($remaining[$l->id] ?? 0), 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $l->status === 'active' ? 'bg-emerald-500/15 text-emerald-700' : ($l->status === 'requested' || $l->status === 'suspended' ? 'bg-amber-500/15 text-amber-700' : 'bg-surface-muted text-ink-muted') }}">{{ $statuses[$l->status] ?? $l->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="select({{ $l->id }})" class="text-sm text-ink underline">Ouvrir</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-ink-muted">Aucun prêt ni avance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($selected)
        <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-ink">{{ $types[$selected->type] ?? $selected->type }} · {{ $selectedName }}</h2>
                    <p class="text-xs text-ink-muted">
                        {{ number_format($selected->principal, 0, ',', ' ') }} accordés · échéance {{ number_format($selected->installment_amount, 0, ',', ' ') }}
                        @if ($selectedEffort !== null) · taux d'effort indicatif {{ str_replace('.', ',', (string) $selectedEffort) }} % @endif
                        @if ($selected->interest_rate !== null) · intérêt indicatif {{ str_replace('.', ',', (string) $selected->interest_rate) }} % @endif
                        · {{ $statuses[$selected->status] ?? $selected->status }}
                        @if ($selected->approved_at) · approuvé le {{ $selected->approved_at->format('d/m/Y') }} @endif
                        @if ($selected->disbursed_on) · décaissé le {{ $selected->disbursed_on->format('d/m/Y') }} @endif
                    </p>
                    @if ($selected->notes) <p class="mt-1 text-xs text-ink-muted">{{ $selected->notes }}</p> @endif
                </div>
                @if ($canDelete && in_array($selected->status, ['requested', 'cancelled'], true))
                    <button type="button" wire:click="deleteLoan({{ $selected->id }})" wire:confirm="Supprimer cette demande ?" class="rounded-lg border border-line px-3 py-1.5 text-sm text-red-500">Supprimer</button>
                @endif
            </div>

            @if ($canEdit)
                <div class="flex flex-wrap items-start gap-3 rounded-lg bg-surface-muted p-3">
                    @if ($selected->status === 'requested')
                        <button type="button" wire:click="approve({{ $selected->id }})" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Approuver</button>
                        <button type="button" wire:click="cancel({{ $selected->id }})" wire:confirm="Annuler cette demande ?" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Annuler la demande</button>
                    @elseif ($selected->status === 'approved')
                        <input type="date" wire:model="disbursedOn" class="input w-44">
                        <button type="button" wire:click="disburse({{ $selected->id }})" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Enregistrer le décaissement</button>
                        <button type="button" wire:click="cancel({{ $selected->id }})" wire:confirm="Annuler ce prêt approuvé ?" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Annuler</button>
                        @error('disbursedOn') <p class="w-full text-xs text-red-500">{{ $message }}</p> @enderror
                    @elseif ($selected->status === 'active')
                        <div>
                            <input type="month" wire:model="suspendFrom" class="input w-44">
                            @error('suspendFrom') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="suspend({{ $selected->id }})" wire:confirm="Suspendre les échéances à partir de ce mois ?" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Suspendre à partir de ce mois</button>
                    @elseif ($selected->status === 'suspended')
                        <div>
                            <input type="month" wire:model="resumeMonth" class="input w-44">
                            @error('resumeMonth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="resume({{ $selected->id }})" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Reprendre à ce mois</button>
                    @endif

                    @if ($selected->status === 'active' || $selected->status === 'suspended')
                        <div class="flex items-start gap-2">
                            <div>
                                <input type="text" inputmode="numeric" wire:model="earlyAmount" class="input w-40 text-right" placeholder="Montant remboursé">
                                @error('earlyAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="earlyRepay({{ $selected->id }})" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Remboursement anticipé</button>
                        </div>
                    @endif
                </div>
            @endif

            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                    <tr><th class="py-2 pr-4">Mois</th><th class="py-2 pr-4 text-right">Montant</th><th class="py-2">État</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($selected->installments as $i)
                        <tr wire:key="ins-{{ $i->id }}">
                            <td class="py-2 pr-4 text-ink">{{ $i->due_month }}</td>
                            <td class="py-2 pr-4 text-right text-ink">{{ number_format($i->amount, 0, ',', ' ') }}</td>
                            <td class="py-2 text-ink-muted">
                                {{ $instStatuses[$i->status] ?? $i->status }}
                                @if ($i->deducted_at) · le {{ $i->deducted_at->format('d/m/Y') }} @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
