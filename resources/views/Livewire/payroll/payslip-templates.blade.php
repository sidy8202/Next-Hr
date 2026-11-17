<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Modèles de bulletin</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Réglages d'affichage du bulletin de paie. La mise en page PDF et l'envoi viendront plus tard : ces réglages seront alors appliqués tels quels.
            </p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="newTemplate" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouveau modèle de bulletin</button>
        @endif
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">{{ $editingId ? 'Modifier le modèle de bulletin' : 'Nouveau modèle de bulletin' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="name">Nom</label>
                    <input id="name" type="text" wire:model="name" class="input w-full" placeholder="Ex. Bulletin standard">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="convention">Convention collective affichée</label>
                    <input id="convention" type="text" wire:model="convention" class="input w-full">
                    @error('convention') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="show_employer_part" class="mt-0.5">
                    <span>Afficher la part employeur<span class="block text-xs text-ink-muted">Masquée par défaut.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="show_item_codes" class="mt-0.5">
                    <span>Afficher les codes de rubriques<span class="block text-xs text-ink-muted">Ex. 100200 devant « Indemnité spéciale ».</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="group_lines" class="mt-0.5">
                    <span>Regrouper les lignes<span class="block text-xs text-ink-muted">Gains, puis retenues, puis avantages, avec un sous-total.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" wire:model="is_default" class="mt-0.5">
                    <span>Modèle par défaut</span>
                </label>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink" for="footer_text">Mentions en pied de bulletin</label>
                <textarea id="footer_text" rows="3" wire:model="footer_text" class="input w-full" placeholder="Ex. Bulletin à conserver sans limitation de durée."></textarea>
                @error('footer_text') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Annuler</button>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($templates as $t)
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-line bg-surface p-4" wire:key="tpl-{{ $t->id }}">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-ink">{{ $t->name }}</span>
                        @if ($t->is_default) <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Par défaut</span> @endif
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">
                        @if ($t->convention) {{ $t->convention }} · @endif
                        Part employeur {{ $t->show_employer_part ? 'affichée' : 'masquée' }} ·
                        codes {{ $t->show_item_codes ? 'affichés' : 'masqués' }} ·
                        lignes {{ $t->group_lines ? 'regroupées' : 'à plat' }}
                    </p>
                    @if ($t->footer_text) <p class="mt-1 text-xs text-ink-muted">{{ $t->footer_text }}</p> @endif
                </div>
                @if ($canEdit)
                    <div class="flex items-center gap-3 text-sm whitespace-nowrap">
                        <button type="button" wire:click="editTemplate({{ $t->id }})" class="text-ink underline">Modifier</button>
                        @if (! $t->is_default)
                            <button type="button" wire:click="makeDefault({{ $t->id }})" class="text-ink-muted underline">Mettre par défaut</button>
                        @endif
                        <button type="button" wire:click="deleteTemplate({{ $t->id }})" wire:confirm="Supprimer ce modèle de bulletin ?" class="text-red-500">Supprimer</button>
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-xl border border-line bg-surface p-6 text-center text-sm text-ink-muted">Aucun modèle de bulletin. Créez le premier avec « Nouveau modèle de bulletin ».</p>
        @endforelse
    </div>
</div>
