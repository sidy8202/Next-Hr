<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-ink">Paquets par secteur</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Rubriques, modèles de traitement, grilles et modèle de bulletin proposés aux entreprises dans « Démarrage rapide ». Un paquet doit être validé par un comptable ; un paquet validé ne se modifie plus : on crée une nouvelle version. Aucune valeur légale ou conventionnelle ne doit être saisie sans source.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="seedStarters" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Charger les paquets de départ</button>
            <button type="button" wire:click="newPackage" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Nouveau paquet</button>
        </div>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    @if ($pending > 0)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
            {{ $pending }} version(s) de paquet restent à valider. Les entreprises voient les paquets non validés avec un bandeau d'avertissement.
        </div>
    @endif

    @if ($formMode)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <h2 class="text-base font-semibold text-ink">
                @if ($formMode === 'new') Nouveau paquet @else Modifier {{ $code }} (non validé) @endif
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-code">Code</label>
                    <input id="pkg-code" type="text" wire:model="code" class="input w-full" placeholder="commerce" @disabled($formMode !== 'new')>
                    @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-name">Nom</label>
                    <input id="pkg-name" type="text" wire:model="name" class="input w-full">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-sector">Secteur</label>
                    <input id="pkg-sector" type="text" wire:model="sector" class="input w-full">
                    @error('sector') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-source">Source du contenu</label>
                    <input id="pkg-source" type="text" wire:model="source" class="input w-full" placeholder="Convention collective, bulletin, comptable…">
                    @error('source') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-description">Description</label>
                    <textarea id="pkg-description" wire:model="description" rows="2" class="input w-full"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-ink" for="pkg-payload">Contenu (JSON : items, profiles, grids, template, settings)</label>
                    <textarea id="pkg-payload" wire:model="payloadJson" rows="16" class="input w-full font-mono text-xs"></textarea>
                    @error('payloadJson') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            @if (count($payloadErrors) > 0)
                <div class="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-600">
                    <p class="font-medium">Le contenu contient {{ count($payloadErrors) }} erreur(s) :</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        @foreach ($payloadErrors as $err)
                            <li wire:key="perr-{{ $loop->index }}">{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Enregistrer</button>
                <button type="button" wire:click="cancel" class="text-sm text-ink underline">Annuler</button>
            </div>
        </form>
    @endif

    @if (count($groups) === 0)
        <div class="rounded-xl border border-line bg-surface p-5 text-sm text-ink-muted">
            Aucun paquet. Cliquez sur « Charger les paquets de départ » pour créer Commerce, Transit et les coquilles vides des autres secteurs.
        </div>
    @endif

    @foreach ($groups as $groupCode => $versions)
        <div wire:key="grp-{{ $groupCode }}" class="rounded-xl border border-line bg-surface">
            <div class="border-b border-line px-5 py-3">
                <p class="text-sm font-semibold text-ink">{{ $versions[0]['name'] }} <span class="font-normal text-ink-muted">· {{ $groupCode }} @if ($versions[0]['sector']) · {{ $versions[0]['sector'] }} @endif</span></p>
            </div>
            <div class="divide-y divide-line">
                @foreach ($versions as $v)
                    <div wire:key="ver-{{ $v['id'] }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0 space-y-0.5">
                            <p class="text-sm text-ink">
                                Version {{ $v['version'] }}
                                @if ($v['empty']) <span class="text-ink-muted">· coquille vide (jamais proposée aux entreprises)</span> @elseif ($v['contents']) <span class="text-ink-muted">· {{ $v['contents'] }}</span> @endif
                            </p>
                            @if ($v['source']) <p class="text-xs text-ink-muted">Source : {{ $v['source'] }}</p> @endif
                            @if ($v['applications'] > 0) <p class="text-xs text-ink-muted">Appliquée {{ $v['applications'] }} fois par des entreprises.</p> @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($v['validated'])
                                <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Validé {{ $v['validated_info'] }}</span>
                                <button type="button" wire:click="newVersion({{ $v['id'] }})" class="text-sm text-ink underline">Nouvelle version</button>
                            @else
                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">À valider</span>
                                <button type="button" wire:click="editPackage({{ $v['id'] }})" class="text-sm text-ink underline">Modifier</button>
                                <button type="button" wire:click="validatePackage({{ $v['id'] }})" wire:confirm="Valider cette version ? Elle ne pourra plus être modifiée." class="text-sm text-ink underline">Valider</button>
                                <button type="button" wire:click="deletePackage({{ $v['id'] }})" wire:confirm="Supprimer cette version ?" class="text-sm text-red-500 underline">Supprimer</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
