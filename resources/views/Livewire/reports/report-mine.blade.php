<div class="space-y-6">
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold">Mes rapports</h3>
        <p class="mb-4 text-xs text-ink-muted">Ouvrir un rapport rétablit ses critères. Pour en ajouter un, ouvrez un rapport, réglez ses critères, puis « Enregistrer comme mon rapport ».</p>

        <ul class="divide-y divide-line text-sm">
            @forelse ($mine as $r)
                <li wire:key="mine-{{ $r['id'] }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ $r['name'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $r['title'] }} · {{ $r['visibility'] === 'shared' ? 'Partagé avec l’entreprise' : 'Privé' }} · mis à jour le {{ $r['updated'] }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="openSaved({{ $r['id'] }})" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Ouvrir</button>
                        <button type="button" wire:click="toggleShare({{ $r['id'] }})" class="rounded-lg border border-line px-3 py-1.5 text-xs hover:bg-surface-muted">{{ $r['visibility'] === 'shared' ? 'Rendre privé' : 'Partager' }}</button>
                        <button type="button" wire:click="deleteSaved({{ $r['id'] }})" wire:confirm="Supprimer ce rapport enregistré ?" class="rounded-lg border border-line px-3 py-1.5 text-xs text-rose-600 hover:bg-surface-muted">Supprimer</button>
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-ink-muted">Vous n’avez encore enregistré aucun rapport.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold">Partagés avec moi</h3>
        <p class="mb-4 text-xs text-ink-muted">Rapports partagés par vos collègues, limités à ceux que vos droits vous permettent d’ouvrir.</p>

        <ul class="divide-y divide-line text-sm">
            @forelse ($shared as $r)
                <li wire:key="shared-{{ $r['id'] }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ $r['name'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $r['title'] }} · par {{ $r['owner'] }} · mis à jour le {{ $r['updated'] }}</p>
                    </div>
                    <button type="button" wire:click="openSaved({{ $r['id'] }})" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">Ouvrir</button>
                </li>
            @empty
                <li class="py-8 text-center text-ink-muted">Aucun rapport partagé pour l’instant.</li>
            @endforelse
        </ul>
    </section>
</div>
