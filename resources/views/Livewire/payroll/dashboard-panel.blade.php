<div class="space-y-6">
    {{-- Assistant « Démarrer la paie » : rappel tant qu'il reste des étapes obligatoires --}}
    @if ($setupBanner)
        <a href="{{ $setupBanner['url'] }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 dark:border-amber-500/40 dark:bg-amber-500/10">
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-ink">La mise en route de la paie n’est pas terminée</span>
                <span class="block text-xs text-ink-muted">{{ $setupBanner['percent'] }} % fait · {{ $setupBanner['remaining'] }} {{ $setupBanner['remaining'] > 1 ? 'étapes obligatoires restent' : 'étape obligatoire reste' }} à faire.</span>
            </span>
            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Continuer →</span>
        </a>
    @endif

    {{-- Choix du mois --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface px-5 py-4 shadow-sm">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousMonth" wire:loading.attr="disabled" aria-label="Mois précédent" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink hover:bg-surface-muted">←</button>
            <div class="min-w-[9rem] text-center">
                <p class="text-base font-semibold capitalize text-ink">{{ $monthLabel }}</p>
                <p class="text-xs text-ink-muted">{{ $statusLabel }}</p>
            </div>
            <button type="button" wire:click="nextMonth" wire:loading.attr="disabled" aria-label="Mois suivant" @disabled(! $canNext) class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-40">→</button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($provisional)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">Chiffres provisoires : la paie n’est pas clôturée</span>
            @elseif ($status === 'closed')
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">Clôturée{{ $closedAt ? ' le ' . $closedAt : '' }}</span>
            @endif
            @unless ($isCurrentMonth)
                <button type="button" wire:click="currentMonth" wire:loading.attr="disabled" class="rounded-lg border border-line px-3 py-1.5 text-xs text-ink hover:bg-surface-muted">Revenir au mois à traiter</button>
            @endunless
        </div>
    </div>

    {{-- Cycle du mois --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-ink">Cycle de paie du mois</h3>
                <p class="text-xs text-ink-muted">{{ $cycle['done'] }} étape{{ $cycle['done'] > 1 ? 's' : '' }} sur {{ $cycle['total'] }} faite{{ $cycle['done'] > 1 ? 's' : '' }}.</p>
            </div>
            <div class="h-2 w-40 overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $cycle['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $cycle['percent'] }}%"></div>
            </div>
        </div>

        <ol class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cycle['steps'] as $i => $step)
                <li wire:key="cycle-{{ $step['key'] }}" @class([
                    'flex flex-col rounded-xl border p-4',
                    'border-emerald-300 bg-emerald-50/60 dark:border-emerald-500/40 dark:bg-emerald-500/10' => $step['status'] === 'done',
                    'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-300 dark:border-indigo-400/60 dark:bg-indigo-500/10 dark:ring-indigo-400/40' => $step['status'] === 'current',
                    'border-line bg-surface-muted/50' => $step['status'] === 'waiting',
                ])>
                    <div class="flex items-center gap-2">
                        <span @class([
                            'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-emerald-500 text-white' => $step['status'] === 'done',
                            'bg-indigo-600 text-white' => $step['status'] === 'current',
                            'bg-line text-ink-muted' => $step['status'] === 'waiting',
                        ])>{{ $step['status'] === 'done' ? '✓' : $i + 1 }}</span>
                        <p class="text-sm font-semibold text-ink">{{ $step['title'] }}</p>
                    </div>
                    <p class="mt-2 flex-1 text-xs text-ink-muted">{{ $step['detail'] }}</p>

                    @if ($step['status'] === 'current')
                        @if ($step['url'])
                            <a href="{{ $step['url'] }}" class="mt-3 inline-flex w-fit rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">{{ $step['target']['label'] }} →</a>
                        @else
                            <p class="mt-3 text-xs italic text-ink-muted">À faire par le service de paie.</p>
                        @endif
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Alertes --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-ink">À surveiller</h3>
        <p class="mb-4 text-xs text-ink-muted">Des nombres seulement : le détail se trouve dans l’écran concerné.</p>

        @forelse ($alerts as $a)
            <div wire:key="alert-{{ $a['key'] }}" @class([
                'mb-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3 last:mb-0',
                'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' => $a['severity'] === 'critical',
                'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' => $a['severity'] === 'warning',
                'border-line bg-surface-muted/50' => $a['severity'] === 'info',
            ])>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-ink">{{ $a['title'] }}</p>
                    <p class="text-xs text-ink-muted">{{ $a['detail'] }}</p>
                </div>
                @if ($a['url'])
                    <a href="{{ $a['url'] }}" class="shrink-0 text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-300">{{ $a['target']['label'] }} →</a>
                @endif
            </div>
        @empty
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">Rien à signaler pour ce mois.</p>
        @endforelse
    </section>

    {{-- Chiffres du mois --}}
    @if ($count > 0)
        <div>
            <p class="mb-3 text-xs text-ink-muted">{{ $count }} bulletin{{ $count > 1 ? 's' : '' }} · comparaison avec {{ $previousLabel }}.</p>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($kpis as $k)
                    <div wire:key="kpi-{{ $loop->index }}">
                        <x-platform.kpi :label="$k['label']" :value="$k['value']" suffix=" FCFA" :color="$k['color']" :hint="$k['hint']" :delay="$loop->index * 60" />
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="rounded-2xl border border-line bg-surface p-8 text-center text-sm text-ink-muted">
            @if ($status === 'none' || $status === 'draft')
                La paie de {{ $monthLabel }} n’est pas encore calculée : les chiffres apparaîtront après le calcul.
            @else
                Aucun bulletin pour {{ $monthLabel }}.
            @endif
        </p>
    @endif

    {{-- Comparaisons : mois précédent et même mois de l'an dernier --}}
    @if (count($comparison) > 0)
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink">Comparaisons</h3>
            <p class="mb-3 text-xs text-ink-muted">Le mois affiché face au mois précédent et au même mois de l’an dernier. « — » : ce mois n’est pas calculé.</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="text-left text-xs text-ink-muted">
                            <th class="px-3 py-2 font-medium">Indicateur</th>
                            <th class="px-3 py-2 text-right font-medium capitalize">{{ $monthLabel }}</th>
                            <th class="px-3 py-2 text-right font-medium capitalize">{{ $previousPeriodLabel }}</th>
                            <th class="px-3 py-2 text-right font-medium capitalize">{{ $lastYearLabel }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison as $r)
                            <tr wire:key="cmp-{{ $r['key'] }}" class="border-t border-line">
                                <td class="px-3 py-2 text-ink">{{ $r['label'] }}</td>
                                <td class="px-3 py-2 text-right font-medium text-ink">{{ $r['current'] }}</td>
                                @foreach (['previous', 'last_year'] as $ref)
                                    <td class="px-3 py-2 text-right align-top">
                                        @if ($r[$ref]['value'] !== null)
                                            <span class="block text-ink">{{ $r[$ref]['value'] }}@if ($r[$ref]['provisional']) <span class="text-xs text-amber-600">(provisoire)</span>@endif</span>
                                            <span class="block text-xs text-ink-muted">{{ $r[$ref]['delta'] }}</span>
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
            <p class="mt-2 text-xs text-ink-muted">Montants en FCFA. L’écart se lit « mois affiché moins mois de comparaison ».</p>
        </section>
    @endif

    {{-- Courbe sur 12 mois --}}
    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">Évolution sur 12 mois</h3>
                <p class="text-xs text-ink-muted">{{ $metricLabel }}, en FCFA, jusqu’à {{ $monthLabel }}.</p>
            </div>
            <div class="inline-flex rounded-lg border border-line p-0.5" role="group" aria-label="Indicateur affiché">
                @foreach ($metrics as $key => $label)
                    <button type="button" wire:key="metric-{{ $key }}" wire:click="setMetric('{{ $key }}')" wire:loading.attr="disabled" @class([
                        'rounded-md px-3 py-1 text-xs font-medium transition',
                        'bg-indigo-600 text-white' => $metric === $key,
                        'text-ink-muted hover:bg-surface-muted' => $metric !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($chart['empty'])
            <p class="rounded-xl bg-surface-muted/60 px-4 py-6 text-center text-sm text-ink-muted">La courbe apparaîtra dès que deux mois au moins seront calculés sur la période.</p>
        @else
            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="h-auto w-full min-w-[520px]" role="img" aria-label="Courbe : {{ $metricLabel }} sur 12 mois">
                    @foreach ($chart['ticks'] as $t)
                        <line x1="{{ $chart['plot']['left'] }}" x2="{{ $chart['plot']['right'] }}" y1="{{ $t['y'] }}" y2="{{ $t['y'] }}" class="stroke-line" stroke-width="1" />
                        <text x="{{ $chart['plot']['left'] - 8 }}" y="{{ $t['y'] + 4 }}" text-anchor="end" class="fill-ink-muted text-[11px]">{{ $t['label'] }}</text>
                    @endforeach

                    @foreach ($chart['paths'] as $d)
                        <path d="{{ $d }}" fill="none" class="stroke-indigo-500" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    @endforeach

                    @foreach ($chart['points'] as $pt)
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="4.5" stroke-width="2" @class([
                            'stroke-indigo-500',
                            'fill-surface' => $pt['provisional'],
                            'fill-indigo-500' => ! $pt['provisional'],
                        ])><title>{{ $pt['title'] }}</title></circle>
                    @endforeach

                    @foreach ($chart['xlabels'] as $xl)
                        <text x="{{ $xl['x'] }}" y="{{ $chart['height'] - 16 }}" text-anchor="middle" class="fill-ink-muted text-[11px]">{{ $xl['text'] }}</text>
                        @if ($xl['year'])
                            <text x="{{ $xl['x'] }}" y="{{ $chart['height'] - 3 }}" text-anchor="middle" class="fill-ink-muted text-[10px]">{{ $xl['year'] }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
            <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-muted">
                <span>● paie clôturée</span>
                <span>○ calculée, pas encore clôturée (provisoire)</span>
                <span>Un mois sans point n’est pas calculé : la courbe ne le devine pas.</span>
            </p>
            @if (count($trendNotes) > 0)
                <ul class="mt-3 space-y-1 text-xs text-ink-muted">
                    @foreach ($trendNotes as $note)
                        <li wire:key="note-{{ $loop->index }}">{{ $note }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>

    {{-- Répartitions du mois --}}
    @if ($departments)
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink">Répartition par service</h3>
                <p class="mb-4 text-xs text-ink-muted">{{ $metricLabel }} de {{ $monthLabel }} : part du total de la paie.</p>

                @forelse ($departments['items'] as $d)
                    <div wire:key="dept-{{ $loop->index }}" class="mb-3 last:mb-0">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="min-w-0 truncate text-ink">{{ $d['name'] }} <span class="text-xs text-ink-muted">· {{ $d['count'] }} {{ $d['count'] > 1 ? 'personnes' : 'personne' }}</span></span>
                            <span class="shrink-0 text-ink">{{ \App\Services\Payroll\PayrollDashboardStats::number($d['value']) }} <span class="text-xs text-ink-muted">FCFA · {{ $d['share'] }} %</span></span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-surface-muted">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $d['bar'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-surface-muted/60 px-4 py-6 text-center text-sm text-ink-muted">Aucun service n’atteint l’effectif minimal d’affichage.</p>
                @endforelse

                @if ($departments['hidden_people'] > 0)
                    <p class="mt-3 text-xs text-ink-muted">{{ $departments['hidden_people'] }} {{ $departments['hidden_people'] > 1 ? 'personnes' : 'personne' }} de petits services ne {{ $departments['hidden_people'] > 1 ? 'sont' : 'est' }} pas affichée{{ $departments['hidden_people'] > 1 ? 's' : '' }} : sous {{ \App\Services\Payroll\PayrollBreakdown::MIN_GROUP }} personnes, le total d’un service révélerait un salaire.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink">Du brut au net</h3>
                <p class="mb-4 text-xs text-ink-muted">Où va la masse brute de {{ $monthLabel }}.</p>

                <div class="flex h-4 w-full overflow-hidden rounded-full bg-surface-muted" role="img" aria-label="Répartition du brut entre net, cotisations, impôt et autres retenues">
                    @foreach ($grossToNet as $g)
                        <div wire:key="g2n-bar-{{ $g['key'] }}" style="width: {{ $g['share'] }}%" @class([
                            'h-full',
                            'bg-emerald-500' => $g['key'] === 'net',
                            'bg-indigo-500' => $g['key'] === 'social',
                            'bg-violet-500' => $g['key'] === 'its',
                            'bg-amber-500' => $g['key'] === 'other',
                        ])></div>
                    @endforeach
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($grossToNet as $g)
                        <li wire:key="g2n-{{ $g['key'] }}" class="flex items-start justify-between gap-3">
                            <span class="flex min-w-0 items-start gap-2 text-ink">
                                <span @class([
                                    'mt-1.5 size-2.5 shrink-0 rounded-full',
                                    'bg-emerald-500' => $g['key'] === 'net',
                                    'bg-indigo-500' => $g['key'] === 'social',
                                    'bg-violet-500' => $g['key'] === 'its',
                                    'bg-amber-500' => $g['key'] === 'other',
                                ])></span>
                                <span>{{ $g['label'] }}</span>
                            </span>
                            <span class="shrink-0 text-ink">{{ \App\Services\Payroll\PayrollDashboardStats::number($g['amount']) }} <span class="text-xs text-ink-muted">FCFA · {{ $g['share'] }} %</span></span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        @if ($composition)
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink">Composition du brut par rubrique</h3>
                <p class="mb-4 text-xs text-ink-muted">Les gains de {{ $monthLabel }}, additionnés sur tous les bulletins ; les plus grosses rubriques d’abord.</p>

                @forelse ($composition['items'] as $it)
                    <div wire:key="comp-{{ $loop->index }}" class="mb-3 last:mb-0">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="min-w-0 truncate text-ink">{{ $it['label'] }}</span>
                            <span class="shrink-0 text-ink">{{ \App\Services\Payroll\PayrollDashboardStats::number($it['amount']) }} <span class="text-xs text-ink-muted">FCFA · {{ $it['share'] }} %</span></span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-surface-muted">
                            <div class="h-full rounded-full bg-sky-500" style="width: {{ $it['share'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-surface-muted/60 px-4 py-6 text-center text-sm text-ink-muted">Aucune rubrique lisible dans les bulletins de ce mois.</p>
                @endforelse

                @if ($composition['deductions'] > 0)
                    <p class="mt-3 text-xs text-ink-muted">Retenues sur le brut (absences, mises à pied) : −{{ \App\Services\Payroll\PayrollDashboardStats::number($composition['deductions']) }} FCFA, déjà déduites du brut.</p>
                @endif

                @unless ($composition['consistent'])
                    <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">Les rubriques lues ne retombent pas exactement sur le brut des bulletins (écart de {{ \App\Services\Payroll\PayrollDashboardStats::signed($composition['gap']) }} FCFA). Recalculez la paie du mois si elle est encore ouverte.</p>
                @endunless
            </section>
        @endif
    @endif

    {{-- Envoi des bulletins (paie clôturée) --}}
    @if ($delivery)
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink">Envoi des bulletins</h3>
            <p class="mb-4 text-xs text-ink-muted">Sur {{ $delivery['total'] }} bulletin{{ $delivery['total'] > 1 ? 's' : '' }} clôturé{{ $delivery['total'] > 1 ? 's' : '' }}.</p>
            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4 xl:grid-cols-7">
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">Envoyés</dt><dd class="text-lg font-semibold text-emerald-600">{{ $delivery['sent'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">Envoi partiel</dt><dd class="text-lg font-semibold text-amber-600">{{ $delivery['partial'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">En échec</dt><dd class="text-lg font-semibold text-rose-600">{{ $delivery['failed'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">Non envoyés</dt><dd class="text-lg font-semibold text-ink">{{ $delivery['not_sent'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">Sans adresse</dt><dd class="text-lg font-semibold text-ink">{{ $delivery['no_address'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">Vus dans l’application</dt><dd class="text-lg font-semibold text-ink">{{ $delivery['seen_app'] }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-3"><dt class="text-xs text-ink-muted">E-mails ouverts</dt><dd class="text-lg font-semibold text-ink">{{ $delivery['opened_email'] }}</dd></div>
            </dl>
        </section>
    @endif
</div>
