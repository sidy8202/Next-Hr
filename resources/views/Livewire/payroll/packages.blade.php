<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <h1 class="text-xl font-semibold text-ink">Démarrage rapide par secteur</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Un paquet crée en un clic les rubriques, les modèles de traitement et le modèle de bulletin de départ de votre secteur. Vous voyez d'abord un aperçu. Rien de ce que vous avez déjà n'est jamais modifié ni supprimé : seul ce qui manque est ajouté. Aucun montant n'est prérempli sans source.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    {{-- Rapport de la dernière application --}}
    @if ($result)
        <div class="space-y-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-5">
            <p class="text-sm font-semibold text-emerald-700">{{ $result['name'] }} (version {{ $result['version'] }}) : {{ $result['totals']['created'] }} créé(s), {{ $result['totals']['skipped'] }} ignoré(s)</p>
            <ul class="space-y-1 text-sm text-ink">
                @foreach ($result['lines'] as $line)
                    <li wire:key="res-{{ $loop->index }}">
                        @if ($line['kind'] === 'create') <span class="text-emerald-700">Créé :</span> @else <span class="text-ink-muted">Ignoré :</span> @endif
                        {{ $line['text'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Paquets proposés --}}
    @if (count($cards) === 0)
        <div class="rounded-xl border border-line bg-surface p-5 text-sm text-ink-muted">
            Aucun paquet n'est disponible pour le moment. La plateforme les prépare par secteur : revenez plus tard, ou configurez vos rubriques et modèles à la main depuis l'assistant « Démarrer la paie ».
        </div>
    @endif

    <div class="space-y-3">
        @foreach ($cards as $c)
            <div wire:key="pkg-{{ $c['id'] }}" class="rounded-xl border border-line bg-surface p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink">{{ $c['name'] }} <span class="font-normal text-ink-muted">· version {{ $c['version'] }}</span></p>
                        @if ($c['contents']) <p class="mt-1 text-sm text-ink-muted">Contient : {{ $c['contents'] }}.</p> @endif
                        @if ($c['description']) <p class="mt-1 text-sm text-ink-muted">{{ $c['description'] }}</p> @endif
                        @if ($c['source']) <p class="mt-1 text-xs text-ink-muted">Source : {{ $c['source'] }}</p> @endif
                    </div>
                    @if ($c['validated'])
                        <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Validé par un comptable @if ($c['validated_on']) le {{ $c['validated_on'] }} @endif</span>
                    @else
                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">Non validé par un comptable</span>
                    @endif
                </div>

                @if (! $c['selected'])
                    <div class="mt-3">
                        <button type="button" wire:click="select({{ $c['id'] }})" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Voir l'aperçu</button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Aperçu du paquet choisi --}}
    @if ($selected)
        <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
            <div>
                <p class="text-sm font-semibold text-ink">Aperçu : {{ $selected['name'] }} (version {{ $selected['version'] }})</p>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ $selected['totals']['created'] }} élément(s) seraient créés, {{ $selected['totals']['skipped'] }} existent déjà et resteraient tels quels.
                </p>
            </div>

            @if (! $selected['validated'])
                <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                    Ce paquet n'a pas encore été validé par un comptable. Les qualifications (cotisable, imposable) sont des points de départ à faire confirmer avant d'utiliser ces rubriques sur une paie réelle.
                </div>
            @endif

            <ul class="space-y-1 text-sm text-ink">
                @foreach ($selected['lines'] as $line)
                    <li wire:key="prev-{{ $loop->index }}">
                        @if ($line['kind'] === 'create') <span class="text-emerald-700">Sera créé :</span> @else <span class="text-ink-muted">Existe déjà, ignoré :</span> @endif
                        {{ $line['text'] }}
                    </li>
                @endforeach
            </ul>

            @if (count($selected['missing']) > 0)
                <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">
                    Votre profil ne permet pas d'appliquer ce paquet. Droits manquants : {{ implode(', ', $selected['missing']) }}.
                </div>
            @elseif ($canEdit)
                @if (! $selected['validated'])
                    <div>
                        <label class="flex items-start gap-2 text-sm text-ink">
                            <input type="checkbox" wire:model="ack" class="mt-1">
                            <span>J'ai compris que ce paquet n'est pas encore validé par un comptable et que je dois faire confirmer ses qualifications.</span>
                        </label>
                        @error('ack') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="apply" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Appliquer ce paquet</button>
                    <button type="button" wire:click="cancel" class="text-sm text-ink underline">Annuler</button>
                </div>
            @endif
        </div>
    @endif

    {{-- Historique --}}
    @if (count($history) > 0)
        <div class="space-y-2 rounded-xl border border-line bg-surface p-5">
            <p class="text-sm font-semibold text-ink">Paquets déjà appliqués</p>
            <div class="divide-y divide-line">
                @foreach ($history as $h)
                    <div wire:key="hist-{{ $loop->index }}" class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                        <span class="text-ink">{{ $h['name'] }} <span class="text-ink-muted">· version {{ $h['version'] }} · {{ $h['when'] }}</span></span>
                        <span class="text-ink-muted">{{ $h['created'] }} créé(s), {{ $h['skipped'] }} ignoré(s) @if (! $h['validated']) · non validé @endif</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
