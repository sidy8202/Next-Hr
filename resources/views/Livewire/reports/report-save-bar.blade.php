<section class="no-print rounded-2xl border border-line bg-surface p-4 shadow-sm" x-data="{ open: false }" x-on:report-saved.window="open = false">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-muted">Ce rapport, avec ces critères, peut être gardé dans « Mes rapports ».</p>
        <button type="button" @click="open = ! open" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm font-medium hover:bg-surface-muted">Enregistrer comme mon rapport</button>
    </div>

    <div x-show="open" x-cloak class="mt-3 space-y-2">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <label class="mb-1 block text-xs font-medium text-ink-muted" for="saveName">Nom du rapport</label>
                <input id="saveName" type="text" maxlength="60" wire:model="saveName" class="input py-1.5 text-sm" placeholder="Ex. Coût de la paie, trimestre">
                @error('saveName')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-muted" for="saveVisibility">Visibilité</label>
                <select id="saveVisibility" wire:model="saveVisibility" class="input py-1.5 text-sm">
                    <option value="private">Privé : moi seul</option>
                    <option value="shared">Partagé avec l’entreprise</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="saveReport" wire:loading.attr="disabled" wire:target="saveReport" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">Enregistrer</button>
            </div>
        </div>
        <p class="text-xs text-ink-muted">Un rapport partagé apparaît chez ceux qui ont le droit d’ouvrir ce rapport. Le partage ne donne jamais accès à des données : chacun voit ce que ses droits permettent.</p>
    </div>
</section>
