<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Simulation de paie</h1>
        <p class="mt-1 text-sm text-ink-muted">
            @if ($mode === 'hypothetical')
                Essayez la paie d'une personne qui n'existe pas encore : une embauche, une augmentation à l'étude. Partez du salaire de base, du brut ou du net voulu, et comparez jusqu'à {{ $maxScenarios }} scénarios côte à côte. Rien n'est enregistré.
            @else
                Calcule la paie d'un employé pour un mois et explique chaque ligne. Rien n'est enregistré : c'est un essai, pour vérifier que les paramètres, les rubriques et les variables donnent le bon résultat.
            @endif
        </p>
    </div>

    <div class="inline-flex rounded-lg border border-line p-0.5" role="group" aria-label="Type de simulation">
        <button type="button" wire:click="setMode('employee')" @class([
            'rounded-md px-4 py-1.5 text-sm font-medium transition',
            'bg-indigo-600 text-white' => $mode === 'employee',
            'text-ink-muted hover:bg-surface-muted' => $mode !== 'employee',
        ])>Employé existant</button>
        <button type="button" wire:click="setMode('hypothetical')" @class([
            'rounded-md px-4 py-1.5 text-sm font-medium transition',
            'bg-indigo-600 text-white' => $mode === 'hypothetical',
            'text-ink-muted hover:bg-surface-muted' => $mode !== 'hypothetical',
        ])>Personne hypothétique</button>
    </div>

    @if ($mode === 'employee')
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
    @else
        <div class="rounded-xl border border-line bg-surface p-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink" for="hypPeriod">Mois</label>
                    <input id="hypPeriod" type="month" wire:model.live="period" class="input w-full">
                </div>
                <p class="text-xs text-ink-muted sm:col-span-2">
                    Calcul au barème, pour un mois complet : salaire de base, rubriques du modèle choisi, famille et ancienneté de chaque scénario.
                    Sans primes propres à une personne ni variables du mois. En partant du brut ou du net voulu, le moteur cherche le salaire de base qui y mène (sans sursalaire) ;
                    en partant du salaire de base avec un net visé, il calcule le sursalaire qui atteint ce net. Le net est le net du contrat, avant prêts et retenues individuelles.
                </p>
            </div>
        </div>

        <div @class([
            'grid gap-4',
            'lg:grid-cols-1' => count($scenarios) === 1,
            'lg:grid-cols-2' => count($scenarios) === 2,
            'lg:grid-cols-3' => count($scenarios) >= 3,
        ])>
            @foreach ($scenarios as $i => $sc)
                <section wire:key="scn-{{ $i }}" class="space-y-3 rounded-xl border border-line bg-surface p-4">
                    <div class="flex items-center gap-2">
                        <input type="text" maxlength="40" wire:model.blur="scenarios.{{ $i }}.label" aria-label="Nom du scénario" class="input w-full font-semibold">
                        <button type="button" wire:click="duplicateScenario({{ $i }})" @disabled(count($scenarios) >= $maxScenarios) title="Dupliquer ce scénario" class="rounded-lg border border-line px-2 py-1.5 text-xs text-ink hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-40">Dupliquer</button>
                        @if (count($scenarios) > 1)
                            <button type="button" wire:click="removeScenario({{ $i }})" title="Retirer ce scénario" class="rounded-lg border border-line px-2 py-1.5 text-xs text-rose-600 hover:bg-surface-muted">Retirer</button>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted" for="from-{{ $i }}">Je pars de</label>
                        <select id="from-{{ $i }}" wire:model.live="scenarios.{{ $i }}.from" class="input w-full">
                            @foreach ($fromOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}{{ $key === 'base' ? '' : ' voulu' }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (count($stepGroups) > 0 && ($sc['from'] ?? 'base') === 'base')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="step-{{ $i }}">Partir d'un échelon de la grille</label>
                            <select id="step-{{ $i }}" wire:model.live="scenarios.{{ $i }}.step_id" class="input w-full">
                                <option value="">— Saisir un montant —</option>
                                @foreach ($stepGroups as $g)
                                    <optgroup label="{{ $g['label'] }}">
                                        @foreach ($g['steps'] as $st)
                                            <option value="{{ $st['id'] }}">{{ $st['label'] }} · {{ number_format($st['amount'], 0, ',', ' ') }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted" for="amount-{{ $i }}">
                            @if (($sc['from'] ?? 'base') === 'gross') Brut voulu (FCFA) @elseif (($sc['from'] ?? 'base') === 'net') Net voulu (FCFA) @else Salaire de base (FCFA) @endif
                        </label>
                        <input id="amount-{{ $i }}" type="number" min="0" wire:model.live.debounce.500ms="scenarios.{{ $i }}.amount" class="input w-full text-right">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="marital-{{ $i }}">Situation</label>
                            <select id="marital-{{ $i }}" wire:model.live="scenarios.{{ $i }}.marital_status" class="input w-full">
                                <option value="single">Célibataire</option>
                                <option value="married">Marié(e)</option>
                                <option value="divorced">Divorcé(e)</option>
                                <option value="widowed">Veuf / veuve</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="seniority-{{ $i }}">Ancienneté (années)</label>
                            <input id="seniority-{{ $i }}" type="number" min="0" max="50" wire:model.live.debounce.500ms="scenarios.{{ $i }}.seniority" class="input w-full">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="children-{{ $i }}">Enfants à charge</label>
                            <input id="children-{{ $i }}" type="number" min="0" max="30" wire:model.live.debounce.500ms="scenarios.{{ $i }}.children" class="input w-full">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="infirm-{{ $i }}">Majeurs infirmes</label>
                            <input id="infirm-{{ $i }}" type="number" min="0" max="10" wire:model.live.debounce.500ms="scenarios.{{ $i }}.infirm_adults" class="input w-full">
                        </div>
                    </div>

                    @if (($sc['from'] ?? 'base') === 'base')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="net-{{ $i }}">Net visé (facultatif)</label>
                            <input id="net-{{ $i }}" type="number" min="0" wire:model.live.debounce.500ms="scenarios.{{ $i }}.net" class="input w-full text-right" placeholder="Le moteur calcule le sursalaire">
                        </div>
                    @endif

                    @if (count($profiles) > 0)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-muted" for="profile-{{ $i }}">Modèle de traitement</label>
                            <select id="profile-{{ $i }}" wire:model.live="scenarios.{{ $i }}.pay_profile_id" class="input w-full">
                                <option value="">Aucun</option>
                                @foreach ($profiles as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        @if (count($scenarios) < $maxScenarios)
            <button type="button" wire:click="addScenario" class="rounded-lg border border-dashed border-line px-4 py-2 text-sm text-ink hover:bg-surface-muted">+ Ajouter un scénario</button>
        @endif

        @foreach ($results as $r)
            @if ($r['error'])
                <div wire:key="err-{{ $loop->index }}" class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700"><strong>{{ $r['label'] }}</strong> : {{ $r['error'] }}</div>
            @endif
        @endforeach

        @if ($table && $table['baseline'] !== null)
            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="text-sm font-semibold text-ink">Comparaison</h2>
                <p class="mb-3 text-xs text-ink-muted">Montants mensuels en FCFA. Chaque colonne se compare à « {{ $results[$table['baseline']]['label'] }} » : l'écart se lit « scénario moins {{ $results[$table['baseline']]['label'] }} ».</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="text-left text-xs text-ink-muted">
                                <th class="px-3 py-2 font-medium">Indicateur</th>
                                @foreach ($results as $r)
                                    <th wire:key="th-{{ $loop->index }}" class="px-3 py-2 text-right font-medium">{{ $r['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                <tr wire:key="row-{{ $row['key'] }}" @class(['border-t border-line', 'font-semibold' => $row['key'] === 'net'])>
                                    <td class="px-3 py-2 text-ink">{{ $row['label'] }}</td>
                                    @foreach ($row['cells'] as $cell)
                                        <td wire:key="cell-{{ $row['key'] }}-{{ $loop->index }}" class="px-3 py-2 text-right align-top">
                                            @if ($cell['value'] !== null)
                                                <span class="block text-ink">{{ $cell['value'] }}</span>
                                                @if ($cell['delta'] !== '')
                                                    <span class="block text-xs font-normal text-ink-muted">{{ $cell['delta'] }}</span>
                                                @endif
                                            @else
                                                <span class="text-ink-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @foreach ($notes as $n)
                <div wire:key="note-{{ $loop->index }}" @class([
                    'rounded-lg border px-4 py-3 text-sm',
                    'border-emerald-500/40 bg-emerald-500/10 text-emerald-700' => $n['level'] === 'ok',
                    'border-amber-500/40 bg-amber-500/10 text-amber-700' => $n['level'] === 'warning',
                ])><strong>{{ $n['label'] }}</strong> : {{ $n['text'] }}</div>
            @endforeach

            @foreach ($results as $r)
                @if ($r['summary'] && $r['summary']['warnings'])
                    @foreach ($r['summary']['warnings'] as $w)
                        <div wire:key="warn-{{ $loop->parent->index }}-{{ $loop->index }}" class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs text-amber-700"><strong>{{ $r['label'] }}</strong> : {{ $w }}</div>
                    @endforeach
                @endif
            @endforeach
        @endif
    @endif
</div>
