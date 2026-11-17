<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <h1 class="text-xl font-semibold text-ink">Démarrer la paie</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Une liste de contrôle pour préparer la paie de votre entreprise, étape par étape. L'avancement se calcule tout seul à partir de vos données : rien à cocher à la main, sauf deux étapes à confirmer.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif

    {{-- Règles légales de la plateforme --}}
    <div class="{{ $legal['level'] === 'ok' ? 'rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700' : 'rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700' }}">
        {{ $legal['text'] }}
    </div>

    {{-- Accueil --}}
    @if (! $welcomed)
        <div class="space-y-3 rounded-xl border border-line bg-surface p-5">
            <p class="text-sm font-semibold text-ink">Bienvenue dans la paie</p>
            <p class="text-sm text-ink-muted">
                Il y a {{ $requiredTotal }} étapes obligatoires : paramètres, rubriques, modèles, salaires, affectations, une simulation de contrôle, puis la première paie. Les étapes facultatives sont signalées et peuvent être ignorées. Les montants et taux ne sont jamais préremplis : ils viennent de votre convention et de vos décisions.
            </p>
            @if ($canEdit)
                <button type="button" wire:click="welcomeWizard" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">Commencer</button>
            @endif
        </div>
    @endif

    {{-- Progression --}}
    <div class="space-y-2 rounded-xl border border-line bg-surface p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-ink">
                @if ($complete) Tout est prêt @else Progression : {{ $requiredDone }} étape(s) obligatoire(s) sur {{ $requiredTotal }} @endif
            </p>
            <span class="text-lg font-semibold text-ink">{{ $percent }} %</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted">
            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $percent }}%"></div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
            <p class="text-xs text-ink-muted">
                @if ($complete) Les étapes obligatoires sont faites. Vous pouvez masquer l'assistant. @else La pastille du menu indique le nombre d'étapes obligatoires restantes. @endif
            </p>
            @if ($canEdit)
                @if ($hidden)
                    <button type="button" wire:click="showWizard" class="text-sm text-ink underline">Rouvrir l'assistant (pastille du menu)</button>
                @else
                    <button type="button" wire:click="hideWizard" class="text-sm text-ink underline">Masquer l'assistant</button>
                @endif
            @endif
        </div>
    </div>

    {{-- Étapes --}}
    <div class="space-y-3">
        @foreach ($steps as $s)
            <section x-data="{ open: {{ $s['is_open'] ? 'true' : 'false' }} }" wire:key="step-{{ $s['key'] }}-{{ $s['status'] }}" class="rounded-xl border border-line bg-surface">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                    <span class="min-w-0">
                        <span class="text-sm font-semibold text-ink">{{ $s['number'] }}. {{ $s['title'] }}</span>
                        <span class="block text-xs text-ink-muted">{{ $s['detail'] }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        @if (! $s['required'])
                            <span class="text-xs text-ink-muted">facultatif</span>
                        @endif
                        <span class="{{ $s['status_class'] }}">{{ $s['status_label'] }}</span>
                    </span>
                </button>

                <div x-show="open" class="space-y-4 px-5 pb-5">
                    <p class="text-sm text-ink-muted">{{ $s['why'] }}</p>

                    <div class="rounded-lg border border-line p-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">Exemple</p>
                        <p class="mt-1 text-sm text-ink">{{ $s['example'] }}</p>
                    </div>

                    @if (count($s['values']) > 0)
                        <div class="divide-y divide-line rounded-lg border border-line">
                            @foreach ($s['values'] as $v)
                                <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm" wire:key="val-{{ $s['key'] }}-{{ $loop->index }}">
                                    <span class="text-ink-muted">{{ $v['label'] }}</span>
                                    <span class="text-right font-medium text-ink">{{ $v['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        @if ($s['url'])
                            <a href="{{ $s['url'] }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">{{ $s['route_label'] }}</a>
                        @else
                            <span class="text-xs text-ink-muted">Cet écran est réservé à un autre profil d'utilisateur.</span>
                        @endif

                        @if ($s['extra_url'])
                            <a href="{{ $s['extra_url'] }}" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">{{ $s['extra_label'] }}</a>
                        @endif

                        @if ($s['can_confirm'])
                            <button type="button" wire:click="confirmStep('{{ $s['key'] }}')" wire:loading.attr="disabled" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">
                                Je confirme ces valeurs
                            </button>
                        @endif

                        @if ($s['can_skip'])
                            <button type="button" wire:click="skipStep('{{ $s['key'] }}')" class="text-sm text-ink underline">Ignorer cette étape</button>
                        @endif

                        @if ($s['can_unskip'])
                            <button type="button" wire:click="unskipStep('{{ $s['key'] }}')" class="text-sm text-ink underline">Reprendre cette étape</button>
                        @endif
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>
