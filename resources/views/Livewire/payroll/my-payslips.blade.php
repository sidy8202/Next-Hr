<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Mes bulletins de paie</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Vos bulletins de paie, mois par mois. Ils sont personnels et confidentiels : vous seul y avez accès, avec le service de la paie.
        </p>
    </div>

    @if (! $linked)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
            Votre compte n'est pas relié à une fiche employé : aucun bulletin à afficher. Contactez le service des ressources humaines.
        </div>
    @elseif ($years->isEmpty())
        <p class="rounded-xl border border-line bg-surface p-6 text-center text-sm text-ink-muted">Aucun bulletin disponible pour le moment.</p>
    @endif

    @foreach ($years as $year => $payslips)
        <section class="overflow-hidden rounded-xl border border-line bg-surface" wire:key="year-{{ $year }}">
            <div class="bg-surface-muted px-5 py-3 text-sm font-semibold text-ink">{{ $year }}</div>
            <div class="divide-y divide-line">
                @foreach ($payslips as $p)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4" wire:key="mine-{{ $p->id }}">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $labels[$p->id] }}</p>
                            <p class="text-xs text-ink-muted">
                                Net à payer : <span class="font-medium text-ink">{{ number_format($p->net_to_pay, 0, ',', ' ') }}</span>
                                @if (isset($viewed[$p->id])) · consulté le {{ \Carbon\Carbon::parse($viewed[$p->id])->format('d/m/Y') }} @else · <span class="text-sky-700">nouveau</span> @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('payslips.pdf', $p) }}" target="_blank" rel="noopener" class="rounded-lg bg-ink px-3 py-1.5 font-medium text-surface">Voir</a>
                            <a href="{{ route('payslips.pdf', ['payslip' => $p, 'download' => 1]) }}" class="text-ink underline">Télécharger</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
