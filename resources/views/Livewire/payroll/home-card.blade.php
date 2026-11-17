<div>
    @if ($visible)
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink">Paie · <span class="capitalize">{{ $monthLabel }}</span></h3>
                    <p class="text-xs text-ink-muted">{{ $statusLabel }}@if ($provisional) · chiffres provisoires @endif</p>
                </div>
                @if ($url)
                    <a href="{{ $url }}" class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-300">Ouvrir le tableau de bord paie →</a>
                @endif
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs text-ink-muted">Net à payer</p>
                    @if ($net !== null)
                        <p class="text-xl font-semibold text-ink">{{ $net }} <span class="text-xs font-normal text-ink-muted">FCFA</span></p>
                        <p class="text-xs text-ink-muted">{{ $count }} bulletin{{ $count > 1 ? 's' : '' }} · {{ $netDelta }} vs mois précédent</p>
                    @else
                        <p class="text-sm text-ink-muted">Pas encore calculé.</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs text-ink-muted">Cycle du mois</p>
                    <p class="text-sm font-medium text-ink">
                        @if ($currentStep)
                            À faire : {{ $currentStep['title'] }}
                        @else
                            Cycle terminé
                        @endif
                    </p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $cycle['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $cycle['percent'] }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">{{ $cycle['done'] }} / {{ $cycle['total'] }} étapes faites</p>
                </div>

                <div>
                    <p class="text-xs text-ink-muted">À surveiller</p>
                    @if ($critical + $warning > 0)
                        <p class="text-sm font-medium text-ink">
                            @if ($critical > 0)
                                <span class="text-rose-600">{{ $critical }} critique{{ $critical > 1 ? 's' : '' }}</span>
                            @endif
                            @if ($critical > 0 && $warning > 0) · @endif
                            @if ($warning > 0)
                                <span class="text-amber-600">{{ $warning }} à surveiller</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-emerald-600">Rien à signaler</p>
                    @endif
                </div>
            </div>
        </section>
    @endif
</div>
