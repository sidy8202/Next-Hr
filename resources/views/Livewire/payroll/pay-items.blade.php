<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Rubriques de paie</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Le catalogue de ce que peut contenir un bulletin : gains, retenues, avantages. Chaque rubrique dit si elle est soumise aux cotisations, si elle est imposable, et comment elle se calcule.
            </p>
        </div>
        @if ($canEdit)
            <div class="flex gap-2">
                <button type="button" wire:click="loadStarter" wire:confirm="Ajouter les rubriques de départ qui manquent à votre catalogue ?" class="rounded-lg border border-line bg-surface px-4 py-2 text-sm font-medium text-ink">Charger les rubriques de départ</button>
                <button type="button" wire:click="newItem" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouvelle rubrique</button>
            </div>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($total === 0)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
            Votre catalogue est vide. Le bouton « Charger les rubriques de départ » crée les rubriques système (salaire de base, sursalaire, heures supplémentaires, absences, prêts…) et quelques rubriques courantes, sans aucun montant.
        </div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">
                {{ $editingId ? 'Modifier la rubrique ' . $code : 'Nouvelle rubrique' }}
                @if ($locked) <span class="ml-2 rounded-full bg-surface-muted px-2 py-0.5 text-xs font-normal text-ink-muted">Rubrique système : code, type et calcul verrouillés</span> @endif
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="code">Code</label>
                    <input id="code" type="text" wire:model="code" class="input w-full uppercase" placeholder="100200" @disabled($locked)>
                    <p class="mt-1 text-xs text-ink-muted">Gains en 1xxxxx, retenues en 3xxxxx (comme sur vos bulletins).</p>
                    @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="label">Libellé sur le bulletin</label>
                    <input id="label" type="text" wire:model="label" class="input w-full">
                    @error('label') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="type">Type</label>
                    <select id="type" wire:model="type" class="input w-full" @disabled($locked)>
                        @foreach ($types as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="calc_type">Calcul</label>
                    <select id="calc_type" wire:model.live="calc_type" class="input w-full" @disabled($locked)>
                        @foreach ($calcTypes as $key => $text)
                            @if ($key !== 'system' || $locked)
                                <option value="{{ $key }}">{{ $text }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('calc_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="sort">Ordre d'affichage</label>
                    <input id="sort" type="number" min="0" max="999" wire:model="sort" class="input w-full">
                    @error('sort') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                @if ($calc_type === 'fixed' || $calc_type === 'qty_rate')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="default_amount">{{ $calc_type === 'fixed' ? 'Montant par défaut (FCFA)' : 'Taux par unité (FCFA)' }}</label>
                        <input id="default_amount" type="text" inputmode="numeric" wire:model="default_amount" class="input w-full text-right">
                        <p class="mt-1 text-xs text-ink-muted">Facultatif : chaque modèle ou employé peut imposer sa propre valeur.</p>
                    </div>
                @endif

                @if ($calc_type === 'percent_base' || $calc_type === 'percent_gross' || $calc_type === 'percent_item')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="default_rate">Taux par défaut (%)</label>
                        <input id="default_rate" type="text" inputmode="decimal" wire:model="default_rate" class="input w-full text-right">
                        @error('default_rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($calc_type === 'percent_item')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="ref_item_id">Rubrique de référence</label>
                        <select id="ref_item_id" wire:model="ref_item_id" class="input w-full">
                            <option value="">— Choisir —</option>
                            @foreach ($refOptions as $r)
                                <option value="{{ $r->id }}">{{ $r->code }} · {{ $r->label }}</option>
                            @endforeach
                        </select>
                        @error('ref_item_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($calc_type !== 'system')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="floor_amount">Plancher (FCFA, facultatif)</label>
                        <input id="floor_amount" type="text" inputmode="numeric" wire:model="floor_amount" class="input w-full text-right">
                        @error('floor_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink" for="cap_amount">Plafond (FCFA, facultatif)</label>
                        <input id="cap_amount" type="text" inputmode="numeric" wire:model="cap_amount" class="input w-full text-right">
                    </div>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="subject_to_social" class="mt-0.5">
                    <span>Soumise aux cotisations sociales<span class="block text-xs text-ink-muted">Entre dans l'assiette INPS et AMO.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="taxable" class="mt-0.5">
                    <span>Imposable<span class="block text-xs text-ink-muted">Entre dans la base de l'ITS.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="is_compulsory" class="mt-0.5">
                    <span>Obligatoire<span class="block text-xs text-ink-muted">Prévue par la loi ou la convention (sinon facultative).</span></span>
                </label>
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="show_on_payslip"> Visible sur le bulletin
                </label>
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="is_active"> Rubrique active
                </label>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="notes">Notes</label>
                <textarea id="notes" rows="2" wire:model="notes" class="input w-full"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($calc_type === 'scale')
                <div class="space-y-3 rounded-lg border border-line bg-surface-muted p-4">
                    <h3 class="text-sm font-semibold text-ink">Paliers du barème</h3>
                    @if (! $editingId)
                        <p class="text-sm text-ink-muted">Enregistrez d'abord la rubrique : vous pourrez ensuite ajouter les paliers.</p>
                    @else
                        <p class="text-xs text-ink-muted">À partir de N années d'ancienneté, le taux s'applique (au salaire de base, selon votre convention). Saisissez-le d'après le texte applicable : aucune valeur n'est préremplie.</p>
                        @if ($editing)
                            @forelse ($editing->scaleRows as $row)
                                <div class="flex items-center gap-3 text-sm text-ink">
                                    <span class="w-40">À partir de {{ $row->from_years }} an(s)</span>
                                    <span class="w-24 text-right">{{ rtrim(rtrim(number_format((float) $row->rate, 3, ',', ''), '0'), ',') }} %</span>
                                    @if ($canEdit)
                                        <button type="button" wire:click="deleteScaleRow({{ $row->id }})" class="text-red-500">Retirer</button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-ink-muted">Aucun palier pour le moment.</p>
                            @endforelse
                        @endif
                        <div class="flex flex-wrap items-start gap-2 pt-1">
                            <div>
                                <input type="number" min="0" max="60" wire:model="scaleYears" class="input w-32" placeholder="Années">
                                @error('scaleYears') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="text" inputmode="decimal" wire:model="scaleRate" wire:keydown.enter.prevent="addScaleRow" class="input w-32 text-right" placeholder="Taux %">
                                @error('scaleRate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="addScaleRow" class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink">Ajouter le palier</button>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Fermer</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="flex items-center gap-3">
        <select wire:model.live="filterType" class="input w-64">
            <option value="">Tous les types</option>
            @foreach ($types as $key => $text)
                <option value="{{ $key }}">{{ $text }}</option>
            @endforeach
        </select>
        <span class="text-sm text-ink-muted">{{ $total }} rubrique(s)</span>
    </div>

    @foreach ($groups as $group)
        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="bg-surface-muted px-4 py-3 text-sm font-semibold text-ink">{{ $group['label'] }}</div>
            <div class="divide-y divide-line">
                @foreach ($group['items'] as $i)
                    <div class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 {{ $i->is_active ? '' : 'opacity-60' }}" wire:key="item-{{ $i->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-ink px-2 py-0.5 text-xs font-semibold text-surface">{{ $i->code }}</span>
                                <span class="text-sm font-medium text-ink">{{ $i->label }}</span>
                                @if ($i->is_system) <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Système</span> @endif
                                <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">{{ $i->is_compulsory ? 'Obligatoire' : 'Facultative' }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $i->subject_to_social ? 'bg-sky-500/15 text-sky-700' : 'bg-surface-muted text-ink-muted' }}">Cotisations : {{ $i->subject_to_social ? 'oui' : 'non' }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $i->taxable ? 'bg-sky-500/15 text-sky-700' : 'bg-surface-muted text-ink-muted' }}">Imposable : {{ $i->taxable ? 'oui' : 'non' }}</span>
                                @if (! $i->is_active) <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">Inactive</span> @endif
                            </div>
                            <p class="mt-1 text-xs text-ink-muted">{{ $desc[$i->id] }}</p>
                            @if ($i->notes) <p class="mt-1 text-xs text-ink-muted">{{ $i->notes }}</p> @endif
                        </div>
                        @if ($canEdit || $canDelete)
                            <div class="flex items-center gap-3 text-sm whitespace-nowrap">
                                @if ($canEdit)
                                    <button type="button" wire:click="editItem({{ $i->id }})" class="text-ink underline">Modifier</button>
                                    <button type="button" wire:click="toggleActive({{ $i->id }})" class="text-ink-muted underline">{{ $i->is_active ? 'Désactiver' : 'Activer' }}</button>
                                @endif
                                @if ($canDelete && ! $i->is_system)
                                    <button type="button" wire:click="deleteItem({{ $i->id }})" wire:confirm="Supprimer cette rubrique ?" class="text-red-500">Supprimer</button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
