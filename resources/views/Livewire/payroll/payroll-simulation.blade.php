<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Simulation de paie</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Calcule la paie d'un employé pour un mois et explique chaque ligne. Rien n'est enregistré : c'est un essai, pour vérifier que les paramètres, les rubriques et les variables donnent le bon résultat.
        </p>
    </div>

    <div class="grid gap-4 rounded-xl border border-line bg-surface p-5 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-ink" for="employeeId">Employé</label>
            <select id="employeeId" wire:model.live="employeeId" class="input w-full">
                <option value="">— Choisir —</option>
                @foreach ($employees as $e)
                    <option value="{{ $e['id'] }}">{{ $e['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-ink" for="period">Mois</label>
            <input id="period" type="month" wire:model.live="period" class="input w-full">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-ink" for="infirmAdults">Enfants majeurs infirmes</label>
            <input id="infirmAdults" type="number" min="0" max="10" wire:model.live.debounce.400ms="infirmAdults" class="input w-full">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-ink" for="referenceNet">Net de référence (facultatif)</label>
            <input id="referenceNet" type="text" inputmode="numeric" wire:model.live.debounce.400ms="referenceNet" class="input w-full text-right" placeholder="Ex. le net d'un bulletin existant, pour voir l'écart">
        </div>
    </div>

    @if ($error)
        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">{{ $error }}</div>
    @endif

    @if ($frozen)
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700">{{ $frozen }}</div>
    @endif

    @if ($sim)
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-line bg-surface p-4">
                <p class="text-xs uppercase tracking-wide text-ink-muted">Brut</p>
                <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($sim['totals']['gross'], 0, ',', ' ') }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface p-4">
                <p class="text-xs uppercase tracking-wide text-ink-muted">Net à payer</p>
                <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($sim['totals']['net_to_pay'], 0, ',', ' ') }}</p>
                @if ($gap !== null)
                    <p class="mt-1 text-xs {{ abs($gap) <= 60 ? 'text-emerald-700' : 'text-amber-600' }}">
                        Écart avec la référence : {{ $gap > 0 ? '+' : '' }}{{ number_format($gap, 0, ',', ' ') }}
                    </p>
                @endif
            </div>
            <div class="rounded-xl border border-line bg-surface p-4">
                <p class="text-xs uppercase tracking-wide text-ink-muted">Coût employeur</p>
                <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($sim['totals']['employer_cost'], 0, ',', ' ') }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface p-4">
                <p class="text-xs uppercase tracking-wide text-ink-muted">Employé</p>
                <p class="mt-1 text-sm font-medium text-ink">{{ $meta['employee'] }}</p>
                <p class="text-xs text-ink-muted">
                    {{ $meta['matricule'] }}
                    @if ($meta['hire_date']) · embauché le {{ \Carbon\Carbon::parse($meta['hire_date'])->format('d/m/Y') }} @endif
                    · {{ $meta['seniority_years'] }} an(s) d'ancienneté
                </p>
            </div>
        </div>

        @if (isset($sim['net_target']))
            <div class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-3 text-sm text-sky-700">
                Net garanti : pour obtenir {{ number_format($sim['net_target']['target'], 0, ',', ' ') }} de net convenu, le sursalaire est recalculé à
                <strong>{{ number_format($sim['supplement']['used'], 0, ',', ' ') }}</strong>
                (valeur enregistrée : {{ number_format($sim['supplement']['stored'], 0, ',', ' ') }}).
            </div>
        @endif

        @foreach (array_merge($meta['warnings'], $sim['warnings']) as $w)
            <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">{{ $w }}</div>
        @endforeach

        <div class="space-y-3">
            <details class="rounded-xl border border-line bg-surface" open>
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-ink">1. Gains et brut</summary>
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-line">
                            @foreach ($sim['earnings'] as $l)
                                <tr>
                                    <td class="py-2 pr-3 text-xs text-ink-muted">{{ $l['code'] }}</td>
                                    <td class="py-2 pr-3 text-ink">
                                        {{ $l['label'] }}
                                        @if ($l['note']) <span class="block text-xs text-ink-muted">{{ $l['note'] }}</span> @endif
                                    </td>
                                    <td class="py-2 pr-3 text-xs text-ink-muted">
                                        {{ $l['social'] ? 'cotisable' : 'non cotisable' }} · {{ $l['taxable'] ? 'imposable' : 'non imposable' }}
                                    </td>
                                    <td class="py-2 text-right text-ink">{{ number_format($l['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            @foreach ($sim['gross_deductions'] as $l)
                                <tr>
                                    <td class="py-2 pr-3 text-xs text-ink-muted">{{ $l['code'] }}</td>
                                    <td class="py-2 pr-3 text-ink">
                                        {{ $l['label'] }}
                                        @if ($l['note']) <span class="block text-xs text-ink-muted">{{ $l['note'] }}</span> @endif
                                    </td>
                                    <td class="py-2 pr-3 text-xs text-ink-muted">retenue sur le brut</td>
                                    <td class="py-2 text-right text-red-500">− {{ number_format($l['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold">
                                <td class="py-2 pr-3"></td>
                                <td class="py-2 pr-3 text-ink">Brut</td>
                                <td class="py-2 pr-3 text-xs font-normal text-ink-muted">
                                    assiette des cotisations {{ number_format($sim['totals']['cotisable'], 0, ',', ' ') }} · brut imposable {{ number_format($sim['totals']['taxable_gross'], 0, ',', ' ') }}
                                </td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['gross'], 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="rounded-xl border border-line bg-surface">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-ink">2. Cotisations salariales et ITS</summary>
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                            <tr><th class="py-2 pr-3">Retenue</th><th class="py-2 pr-3 text-right">Base</th><th class="py-2 pr-3 text-right">Taux</th><th class="py-2 text-right">Montant</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (collect($sim['legal'])->where('party', 'employee') as $l)
                                <tr>
                                    <td class="py-2 pr-3 text-ink">{{ $l['label'] }}</td>
                                    <td class="py-2 pr-3 text-right text-ink-muted">{{ number_format($l['base'], 0, ',', ' ') }}</td>
                                    <td class="py-2 pr-3 text-right text-ink-muted">{{ $l['rate'] !== null ? rtrim(rtrim(number_format($l['rate'], 3, ',', ''), '0'), ',') . ' %' : 'barème' }}</td>
                                    <td class="py-2 text-right text-ink">{{ number_format($l['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold">
                                <td class="py-2 pr-3 text-ink" colspan="3">Total des retenues salariales</td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['employee_contributions'], 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            @if ($its)
                <details class="rounded-xl border border-line bg-surface">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-ink">3. Comment l'ITS est calculé</summary>
                    <ol class="list-decimal space-y-2 px-9 pb-5 text-sm text-ink">
                        <li>Base mensuelle de l'ITS (brut imposable moins l'INPS salarié) : <strong>{{ number_format($its['monthly_base'], 0, ',', ' ') }}</strong>, soit <strong>{{ number_format($its['annual_base'], 0, ',', ' ') }}</strong> sur l'année.</li>
                        <li>Impôt brut annuel selon le barème par tranches : <strong>{{ number_format($its['gross_tax'], 2, ',', ' ') }}</strong>.</li>
                        <li>Réduction pour charges de famille : <strong>{{ rtrim(rtrim(number_format($its['family_percent'], 2, ',', ''), '0'), ',') }} %</strong> (situation : {{ $meta['family']['marital_status'] ?: 'non renseignée' }}, {{ $meta['family']['children'] }} enfant(s) à charge, {{ $meta['family']['infirm_adults'] }} majeur(s) infirme(s)). Impôt liquidé : <strong>{{ number_format($its['liquidated'], 2, ',', ' ') }}</strong>.</li>
                        <li>Réduction de {{ rtrim(rtrim(number_format($its['rebate_points'], 2, ',', ''), '0'), ',') }} points du taux réel, appliquée au revenu annuel : <strong>− {{ number_format($its['rebate'], 2, ',', ' ') }}</strong>.</li>
                        <li>ITS annuel : <strong>{{ number_format($its['annual_its'], 2, ',', ' ') }}</strong>, soit <strong>{{ number_format($its['monthly_its'], 2, ',', ' ') }}</strong> par mois, arrondi à <strong>{{ number_format($sim['totals']['its'], 0, ',', ' ') }}</strong>.</li>
                    </ol>
                    @foreach ($meta['notes'] as $n)
                        <p class="px-5 pb-3 text-xs text-ink-muted">{{ $n }}</p>
                    @endforeach
                </details>
            @endif

            <details class="rounded-xl border border-line bg-surface">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-ink">4. Retenues sur le net et net à payer</summary>
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-line">
                            <tr>
                                <td class="py-2 pr-3 text-ink">Net avant autres retenues (brut − retenues salariales)</td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['net_before_other'], 0, ',', ' ') }}</td>
                            </tr>
                            @foreach ($sim['net_deductions'] as $l)
                                <tr>
                                    <td class="py-2 pr-3 text-ink">
                                        {{ $l['label'] }}
                                        <span class="ml-1 text-xs text-ink-muted">
                                            {{ $l['category'] === 'benefit' ? 'avantage en nature' : ($l['category'] === 'internal' ? 'retenue interne' : 'retenue individuelle') }}
                                            @if ($l['note']) · {{ $l['note'] }} @endif
                                        </span>
                                    </td>
                                    <td class="py-2 text-right text-red-500">− {{ number_format($l['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="py-2 pr-3 text-ink-muted">Net convenu (avant prêts et retenues individuelles)</td>
                                <td class="py-2 text-right text-ink-muted">{{ number_format($sim['totals']['net_contract'], 0, ',', ' ') }}</td>
                            </tr>
                            <tr class="font-semibold">
                                <td class="py-2 pr-3 text-ink">Net à payer</td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['net_to_pay'], 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="rounded-xl border border-line bg-surface">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-ink">5. Part employeur et coût total</summary>
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                            <tr><th class="py-2 pr-3">Cotisation</th><th class="py-2 pr-3 text-right">Base</th><th class="py-2 pr-3 text-right">Taux</th><th class="py-2 text-right">Montant</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (collect($sim['legal'])->where('party', 'employer') as $l)
                                <tr>
                                    <td class="py-2 pr-3 text-ink">{{ $l['label'] }}</td>
                                    <td class="py-2 pr-3 text-right text-ink-muted">{{ number_format($l['base'], 0, ',', ' ') }}</td>
                                    <td class="py-2 pr-3 text-right text-ink-muted">{{ rtrim(rtrim(number_format($l['rate'], 3, ',', ''), '0'), ',') }} %</td>
                                    <td class="py-2 text-right text-ink">{{ number_format($l['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold">
                                <td class="py-2 pr-3 text-ink" colspan="3">Total des cotisations patronales</td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['employer_total'], 0, ',', ' ') }}</td>
                            </tr>
                            <tr class="font-semibold">
                                <td class="py-2 pr-3 text-ink" colspan="3">Coût total employeur (brut + cotisations patronales)</td>
                                <td class="py-2 text-right text-ink">{{ number_format($sim['totals']['employer_cost'], 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-ink-muted">Les cotisations patronales sont un coût pour l'entreprise : elles ne sont jamais déduites du net de l'employé.</p>
                </div>
            </details>
        </div>
    @endif
</div>
