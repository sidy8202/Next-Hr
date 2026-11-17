<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Réglages des variables</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Comment l'entreprise compte les jours, valorise une heure ou un jour, et majore les heures supplémentaires. Les valeurs de départ sont des propositions : faites-les confirmer par votre comptable.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    <form wire:submit="save" class="space-y-5 rounded-xl border border-line bg-surface p-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="pay_basis">Salaire de référence d'un jour et d'une heure</label>
                <select id="pay_basis" wire:model="pay_basis" class="input w-full" @disabled(! $canEdit)>
                    <option value="base_supplement">Salaire de base + sursalaire</option>
                    <option value="base">Salaire de base seul</option>
                </select>
                <p class="mt-1 text-xs text-ink-muted">Sert à retenir une absence, une mise à pied, ou à payer des heures et des jours travaillés. La base de jours (30) et les heures du mois se règlent dans « Paramètres de paie ».</p>
                @error('pay_basis') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="suspension_count_mode">Décompte d'une mise à pied</label>
                <select id="suspension_count_mode" wire:model="suspension_count_mode" class="input w-full" @disabled(! $canEdit)>
                    <option value="working">Jours ouvrables</option>
                    <option value="calendar">Jours calendaires</option>
                </select>
                <p class="mt-1 text-xs text-ink-muted">Valeur proposée sur chaque ligne ; vous pouvez la changer ligne par ligne.</p>
                @error('suspension_count_mode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <span class="mb-1 block text-sm font-medium text-ink">Jours non ouvrables (repos hebdomadaire)</span>
            <div class="flex flex-wrap gap-4">
                @foreach ($days as $num => $name)
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" wire:model="rest" value="{{ $num }}" @disabled(! $canEdit)> {{ $name }}
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-ink-muted">Les jours fériés de votre calendrier sont aussi exclus du décompte en jours ouvrables.</p>
            @error('rest.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-3 text-sm text-ink">
            <input type="checkbox" wire:model="unjustified_absence_unpaid" class="mt-0.5" @disabled(! $canEdit)>
            <span>
                Proposer une retenue pour les absences non justifiées
                <span class="block text-xs text-ink-muted">Seuls les jours qui n'ont pas déjà été pris sur le solde de congés sont proposés. Vous confirmez chaque ligne avant qu'elle compte.</span>
            </span>
        </label>

        <div class="max-w-xs">
            <label class="mb-1 block text-sm font-medium text-ink" for="loan_effort_alert_percent">Seuil d'alerte du taux d'effort des prêts (%)</label>
            <input id="loan_effort_alert_percent" type="text" inputmode="decimal" wire:model="loan_effort_alert_percent" class="input w-full text-right" placeholder="Vide = pas d'alerte" @disabled(! $canEdit)>
            <p class="mt-1 text-xs text-ink-muted">Au-delà, une demande doit être confirmée explicitement. À fixer par la direction.</p>
            @error('loan_effort_alert_percent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        @if ($canEdit)
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface" wire:loading.attr="disabled">Enregistrer</button>
            </div>
        @endif
    </form>

    <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
        <div>
            <h2 class="text-base font-semibold text-ink">Majorations des heures supplémentaires et des jours travaillés</h2>
            <p class="text-xs text-ink-muted">Aucun taux n'est prérempli : saisissez ceux qui s'appliquent chez vous (convention, accord d'entreprise). Ils s'offrent ensuite en choix dans les variables du mois.</p>
        </div>

        <div class="space-y-2">
            @forelse ($rates as $r)
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line px-3 py-2 {{ $r->is_active ? '' : 'opacity-60' }}" wire:key="rate-{{ $r->id }}">
                    <span class="min-w-0 flex-1 text-sm text-ink">{{ $r->label }}</span>
                    <span class="text-sm font-medium text-ink">+{{ rtrim(rtrim(number_format($r->percent, 3, ',', ''), '0'), ',') }} %</span>
                    @if ($canEdit)
                        <button type="button" wire:click="toggleRate({{ $r->id }})" class="text-sm text-ink-muted underline">{{ $r->is_active ? 'Désactiver' : 'Activer' }}</button>
                        <button type="button" wire:click="deleteRate({{ $r->id }})" wire:confirm="Supprimer cette majoration ?" class="text-sm text-red-500">Supprimer</button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-ink-muted">Aucune majoration définie.</p>
            @endforelse
        </div>

        @if ($canEdit)
            <div class="flex flex-wrap items-start gap-2 border-t border-line pt-4">
                <div>
                    <input type="text" wire:model="newLabel" wire:keydown.enter.prevent="addRate" class="input w-64" placeholder="Libellé (ex. Heures de nuit)">
                    @error('newLabel') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="text" inputmode="decimal" wire:model="newPercent" wire:keydown.enter.prevent="addRate" class="input w-28 text-right" placeholder="Majoration %">
                    @error('newPercent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="addRate" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Ajouter</button>
            </div>
        @endif
    </div>
</div>
