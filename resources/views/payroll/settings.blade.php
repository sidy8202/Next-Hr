<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Paramètres de paie</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Réglages généraux utilisés pour calculer les bulletins de votre entreprise. Les taux légaux (INPS, AMO, ITS…) ne se règlent pas ici : ils sont maintenus par la plateforme.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    <form wire:submit="save" class="space-y-5 rounded-xl border border-line bg-surface p-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="country">Pays</label>
                <select id="country" wire:model="country" class="input w-full" @disabled(! $canEdit)>
                    <option value="ML">Mali</option>
                </select>
                @error('country') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="convention">Convention collective</label>
                <input id="convention" type="text" wire:model="convention_name" class="input w-full" placeholder="Ex. Convention collective du commerce" @disabled(! $canEdit)>
                @error('convention_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="days_basis">Base de jours d'un mois</label>
                <input id="days_basis" type="number" min="1" max="31" wire:model="days_basis" class="input w-full" @disabled(! $canEdit)>
                <p class="mt-1 text-xs text-ink-muted">Sert à proratiser un mois incomplet (embauche, mise à pied, absence). 30 par défaut.</p>
                @error('days_basis') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="hours_month">Heures du mois</label>
                <input id="hours_month" type="text" inputmode="decimal" wire:model="hours_month" class="input w-full" @disabled(! $canEdit)>
                <p class="mt-1 text-xs text-ink-muted">Sert au taux horaire des heures supplémentaires. 173,33 correspond à 40 h par semaine.</p>
                @error('hours_month') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="rounding">Arrondi des montants</label>
                <select id="rounding" wire:model="rounding" class="input w-full" @disabled(! $canEdit)>
                    <option value="nearest">Au franc le plus proche</option>
                    <option value="floor">Au franc inférieur</option>
                    <option value="ceil">Au franc supérieur</option>
                </select>
                @error('rounding') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-start gap-3 text-sm text-ink">
            <input type="checkbox" wire:model="show_employer_part" class="mt-0.5" @disabled(! $canEdit)>
            <span>
                Afficher la part employeur sur le bulletin
                <span class="block text-xs text-ink-muted">Masquée par défaut. Les cotisations patronales sont de toute façon calculées à part pour le coût employeur.</span>
            </span>
        </label>

        @if ($canEdit)
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface" wire:loading.attr="disabled">Enregistrer</button>
            </div>
        @endif
    </form>
</div>
