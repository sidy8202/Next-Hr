<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Affectations de modèles de traitement</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Rattachez vos employés à un modèle, un par un ou en lot (par département ou par poste). Chaque affectation est datée : un changement ferme la précédente la veille et conserve l'historique.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input w-64" placeholder="Nom ou matricule">
        <select wire:model.live="departmentId" class="input w-52">
            <option value="">Tous les départements</option>
            @foreach ($departments as $id => $text)
                <option value="{{ $id }}">{{ $text }}</option>
            @endforeach
        </select>
        <select wire:model.live="positionId" class="input w-52">
            <option value="">Tous les postes</option>
            @foreach ($positions as $id => $text)
                <option value="{{ $id }}">{{ $text }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" wire:model.live="onlyWithout"> Sans modèle seulement
        </label>
        <span class="text-sm text-ink-muted">
            {{ $total }} employé(s)
            @if ($withoutProfile > 0) · <span class="text-amber-600">{{ $withoutProfile }} sans modèle</span> @endif
        </span>
    </div>

    @if ($canEdit)
        <div class="space-y-3 rounded-xl border border-line bg-surface p-4">
            <div class="flex flex-wrap items-start gap-3">
                <div>
                    <select wire:model="profileId" class="input w-64">
                        <option value="">— Modèle à affecter —</option>
                        @foreach ($profiles as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('profileId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="date" wire:model="effective_from" class="input w-44">
                    @error('effective_from') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <input type="text" wire:model="notes" class="input w-64" placeholder="Note (facultatif)">
                <button type="button" wire:click="assign" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface" wire:loading.attr="disabled">
                    Affecter la sélection ({{ count($selected) }})
                </button>
            </div>
            <div class="flex gap-3 text-sm">
                <button type="button" wire:click="selectAllVisible" class="text-ink underline">Tout sélectionner (liste affichée)</button>
                <button type="button" wire:click="clearSelection" class="text-ink-muted underline">Tout désélectionner</button>
                @error('selected') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                <tr>
                    @if ($canEdit) <th class="w-10 px-4 py-3"></th> @endif
                    <th class="px-4 py-3">Employé</th>
                    <th class="px-4 py-3">Modèle actuel</th>
                    <th class="px-4 py-3">Depuis</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($rows as $r)
                    <tr wire:key="emp-{{ $r['employee']->id }}">
                        @if ($canEdit)
                            <td class="px-4 py-3"><input type="checkbox" wire:model="selected" value="{{ $r['employee']->id }}"></td>
                        @endif
                        <td class="px-4 py-3 text-ink">
                            {{ $r['name'] }}
                            <span class="block text-xs text-ink-muted">{{ $r['employee']->matricule }}</span>
                        </td>
                        @if ($r['current'])
                            <td class="px-4 py-3 text-ink">{{ $r['current']->profile?->name }}</td>
                            <td class="px-4 py-3 text-ink-muted">{{ $r['current']->effective_from->format('d/m/Y') }}</td>
                        @else
                            <td class="px-4 py-3 text-amber-600" colspan="2">Aucun modèle</td>
                        @endif
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="showHistory({{ $r['employee']->id }})" class="text-sm text-ink-muted underline">Historique</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-ink-muted">Aucun employé trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($total > 300)
        <p class="text-xs text-ink-muted">Seuls les 300 premiers résultats sont affichés : affinez les filtres.</p>
    @endif

    @if ($historyId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="assign-history">
            <div class="max-h-[90vh] w-full max-w-2xl space-y-4 overflow-y-auto rounded-xl border border-line bg-surface p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-semibold text-ink">Historique des modèles : {{ $historyName }}</h2>
                    <button type="button" wire:click="closeHistory" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Fermer</button>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                        <tr><th class="py-2 pr-4">Période</th><th class="py-2 pr-4">Modèle</th><th class="py-2">Note</th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($history as $h)
                            <tr>
                                <td class="py-2 pr-4 text-ink">{{ $h->effective_from->format('d/m/Y') }} → {{ $h->effective_until ? $h->effective_until->format('d/m/Y') : 'en cours' }}</td>
                                <td class="py-2 pr-4 text-ink">{{ $h->profile?->name }}</td>
                                <td class="py-2 text-ink-muted">{{ $h->notes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-ink-muted">Aucune affectation.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
