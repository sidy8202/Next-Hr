<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Modèles de traitement</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Un modèle regroupe les rubriques qui s'appliquent à une catégorie de personnel (par exemple « Commerce, employé » ou « Transit, cadre »). Vous l'affectez ensuite aux employés depuis la page « Affectations ».
            </p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="newProfile" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouveau modèle</button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="saveProfile" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">{{ $editingId ? 'Modifier le modèle' : 'Nouveau modèle' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="name">Nom</label>
                    <input id="name" type="text" wire:model="name" class="input w-full" placeholder="Ex. Commerce, employé">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="convention">Convention de référence (facultatif)</label>
                    <input id="convention" type="text" wire:model="convention" class="input w-full">
                    @error('convention') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="description">Description</label>
                    <textarea id="description" rows="2" wire:model="description" class="input w-full"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="is_active"> Modèle actif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancelForm" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="flex flex-wrap gap-2">
        @forelse ($profiles as $p)
            <button type="button" wire:click="selectProfile({{ $p->id }})" wire:key="profile-{{ $p->id }}"
                class="rounded-full border px-3 py-1.5 text-sm {{ $profileId === $p->id ? 'border-ink bg-ink text-surface' : 'border-line bg-surface text-ink' }}">
                {{ $p->name }}
                <span class="opacity-70">· {{ $p->items_count }} rubrique(s) · {{ $assigned[$p->id] ?? 0 }} employé(s)</span>
                @if (! $p->is_active) <span class="opacity-70">· inactif</span> @endif
            </button>
        @empty
            <p class="text-sm text-ink-muted">Aucun modèle. Créez le premier avec « Nouveau modèle ».</p>
        @endforelse
    </div>

    @if ($selected)
        <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-ink">{{ $selected->name }}</h2>
                    <p class="text-xs text-ink-muted">
                        @if ($selected->convention) {{ $selected->convention }} · @endif
                        {{ $selected->is_active ? 'actif' : 'inactif' }}
                        @if ($selected->description) · {{ $selected->description }} @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    @if ($canEdit)
                        <button type="button" wire:click="editProfile({{ $selected->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Modifier</button>
                        <button type="button" wire:click="duplicateProfile({{ $selected->id }})" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Dupliquer</button>
                    @endif
                    @if ($canDelete)
                        <button type="button" wire:click="deleteProfile({{ $selected->id }})" wire:confirm="Supprimer ce modèle ?" class="rounded-lg border border-line px-3 py-1.5 text-sm text-red-500">Supprimer</button>
                    @endif
                </div>
            </div>

            <p class="rounded-lg bg-surface-muted px-3 py-2 text-xs text-ink-muted">
                Le salaire de base et le sursalaire viennent du salaire de l'employé : ils n'ont pas à figurer ici. Une valeur laissée vide reprend la valeur par défaut de la rubrique.
            </p>

            <div class="space-y-2">
                @forelse ($selected->items as $row)
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line px-3 py-2" wire:key="row-{{ $row->id }}">
                        <span class="rounded-md bg-ink px-2 py-0.5 text-xs font-semibold text-surface">{{ $row->item->code }}</span>
                        <span class="min-w-0 flex-1 text-sm text-ink">{{ $row->item->label }}</span>

                        @if ($row->item->calc_type === 'fixed' || $row->item->calc_type === 'qty_rate')
                            <input type="text" inputmode="numeric" wire:model="rowAmounts.{{ $row->id }}" wire:change="saveRow({{ $row->id }})"
                                class="input w-40 text-right" placeholder="{{ $row->item->default_amount !== null ? number_format($row->item->default_amount, 0, ',', ' ') : 'Montant' }}" @disabled(! $canEdit)>
                            <span class="text-xs text-ink-muted">{{ $row->item->calc_type === 'qty_rate' ? 'FCFA / unité' : 'FCFA' }}</span>
                        @elseif ($row->item->calc_type === 'percent_base' || $row->item->calc_type === 'percent_gross' || $row->item->calc_type === 'percent_item')
                            <input type="text" inputmode="decimal" wire:model="rowRates.{{ $row->id }}" wire:change="saveRow({{ $row->id }})"
                                class="input w-28 text-right" placeholder="{{ $row->item->default_rate !== null ? rtrim(rtrim(number_format((float) $row->item->default_rate, 3, ',', ''), '0'), ',') : 'Taux' }}" @disabled(! $canEdit)>
                            <span class="text-xs text-ink-muted">%</span>
                        @else
                            <span class="text-xs text-ink-muted">Barème de la rubrique ({{ $row->item->scaleRows->count() }} palier(s))</span>
                        @endif

                        @if ($canEdit)
                            <button type="button" wire:click="removeRow({{ $row->id }})" class="text-sm text-red-500">Retirer</button>
                        @endif
                        @error('rowAmounts.' . $row->id) <span class="w-full text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">Aucune rubrique dans ce modèle.</p>
                @endforelse
            </div>

            @if ($canEdit)
                <div class="flex flex-wrap items-start gap-2 border-t border-line pt-4">
                    <div>
                        <select wire:model="addItemId" class="input w-72">
                            <option value="">— Ajouter une rubrique —</option>
                            @foreach ($available as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} · {{ $a->label }}</option>
                            @endforeach
                        </select>
                        @error('addItemId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <input type="text" inputmode="numeric" wire:model="addAmount" class="input w-36 text-right" placeholder="Montant (facultatif)">
                    <input type="text" inputmode="decimal" wire:model="addRate" class="input w-28 text-right" placeholder="Taux % (facult.)">
                    <button type="button" wire:click="addItem" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Ajouter</button>
                    @error('addAmount') <p class="w-full text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>
    @endif
</div>
