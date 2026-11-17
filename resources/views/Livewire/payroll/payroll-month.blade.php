<div class="mx-auto w-full max-w-[1800px] space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">Paie du mois</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Tout le traitement mensuel au même endroit : préparer, calculer, contrôler, clôturer. Les réglages (grilles, rubriques, modèles) se font une fois pour toutes ailleurs.
        </p>
    </div>

    @if ($flash)
        <div class="rounded-lg border border-line bg-surface-muted px-4 py-3 text-sm text-ink">{{ $flash }}</div>
    @endif
    @if ($problem)
        <div class="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-600">{{ $problem }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previousMonth" class="rounded-lg border border-line px-3 py-2 text-sm text-ink" aria-label="Mois précédent">←</button>
            <input type="month" wire:model.live="period" class="input w-44">
            <button type="button" wire:click="nextMonth" class="rounded-lg border border-line px-3 py-2 text-sm text-ink" aria-label="Mois suivant">→</button>
        </div>
        <span class="text-base font-semibold text-ink">{{ $monthLabel }}</span>
        @if ($status === 'closed')
            <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-700">Clôturée</span>
        @elseif ($status === 'calculated')
            <span class="rounded-full bg-sky-500/15 px-3 py-1 text-xs font-medium text-sky-700">Calculée, à contrôler</span>
        @else
            <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-medium text-amber-700">À calculer</span>
        @endif
    </div>

    @if ($recent->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach ($recent as $r)
                <button type="button" wire:click="selectPeriod('{{ $r->period }}')" wire:key="recent-{{ $r->id }}"
                    class="rounded-full border px-3 py-1 text-xs {{ $r->period === $period ? 'border-ink bg-ink text-surface' : 'border-line bg-surface text-ink' }}">
                    {{ $r->period }} · {{ $r->status === 'closed' ? 'clôturée' : ($r->status === 'calculated' ? 'calculée' : 'brouillon') }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Étape 1 : préparer --}}
    @if ($status !== 'closed')
        <section x-data="{ open: {{ $status === 'draft' ? 'true' : 'false' }} }" wire:key="step1-{{ $status }}" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">1. Préparer</span>
                <span class="text-xs {{ collect($checklist)->contains('level', 'warn') ? 'text-amber-600' : 'text-emerald-700' }}">
                    {{ collect($checklist)->where('level', 'warn')->count() > 0 ? collect($checklist)->where('level', 'warn')->count() . ' point(s) à regarder' : 'Prêt à calculer' }}
                </span>
            </button>
            <div x-show="open" class="space-y-2 px-5 pb-5">
                @foreach ($checklist as $c)
                    <div class="flex items-start gap-3 rounded-lg border border-line px-3 py-2" wire:key="chk-{{ $loop->index }}">
                        <span class="mt-0.5 text-sm {{ $c['level'] === 'ok' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $c['level'] === 'ok' ? '✓' : '!' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink">{{ $c['title'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $c['detail'] }}</p>
                        </div>
                        @if ($c['href'])
                            <a href="{{ $c['href'] }}" class="whitespace-nowrap text-sm text-ink underline">Ouvrir</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Étape 2 : calculer --}}
        <section x-data="{ open: {{ $status === 'draft' ? 'true' : 'false' }} }" wire:key="step2-{{ $status }}" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">2. Calculer</span>
                <span class="text-xs text-ink-muted">
                    @if ($periodModel && $periodModel->calculated_at) Dernier calcul le {{ $periodModel->calculated_at->format('d/m/Y à H:i') }} @else Pas encore calculée @endif
                </span>
            </button>
            <div x-show="open" class="space-y-3 px-5 pb-5">
                <p class="text-sm text-ink-muted">
                    Un seul bouton calcule la paie de tous les employés. Recalculer remplace les bulletins provisoires : rien n'est définitif avant la clôture.
                </p>
                @if ($canCalculate)
                    <button type="button" wire:click="calculate" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">
                        <span wire:loading.remove wire:target="calculate">{{ $status === 'calculated' ? 'Recalculer' : 'Calculer' }} la paie de {{ $monthLabel }}</span>
                        <span wire:loading wire:target="calculate">Calcul en cours…</span>
                    </button>
                @endif

                @if (! empty($stored['skipped']))
                    <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                        <p class="font-medium">{{ count($stored['skipped']) }} employé(s) non traité(s) :</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5">
                            @foreach ($stored['skipped'] as $s)
                                <li>{{ $s['employee'] }}{{ $s['matricule'] ? ' (' . $s['matricule'] . ')' : '' }} : {{ $s['reason'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (! empty($stored['inactive']))
                    <p class="text-xs text-ink-muted">{{ $stored['inactive'] }} employé(s) ne sont pas concernés ce mois-ci (pas encore embauchés ou déjà partis).</p>
                @endif
            </div>
        </section>
    @endif

    {{-- Étape 3 : contrôler --}}
    @if ($payslips->isNotEmpty())
        <section x-data="{ open: {{ $status === 'calculated' ? 'true' : 'false' }} }" wire:key="step3-{{ $status }}" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">3. Contrôler les nets</span>
                <span class="text-xs {{ $summary['statuses']['to_check'] > 0 ? 'text-amber-600' : 'text-emerald-700' }}">
                    {{ $summary['statuses']['to_check'] > 0 ? $summary['statuses']['to_check'] . ' bulletin(s) ont un net différent' : 'Aucun net différent' }}
                </span>
            </button>
            <div x-show="open" class="space-y-4 px-5 pb-5">
                <div class="grid gap-3 sm:grid-cols-4">
                    <div class="rounded-lg border border-line p-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">Net connu</p>
                        <p class="mt-1 text-lg font-semibold text-ink">{{ number_format($summary['compared']['net_known'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-ink-muted">{{ $summary['compared']['count'] }} employé(s) comparé(s)</p>
                    </div>
                    <div class="rounded-lg border border-line p-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">Net calculé</p>
                        <p class="mt-1 text-lg font-semibold text-ink">{{ number_format($summary['compared']['net_calculated'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-ink-muted">pour les mêmes employés</p>
                    </div>
                    <div class="rounded-lg border border-line p-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">Écart</p>
                        <p class="mt-1 text-lg font-semibold {{ $summary['compared']['gap'] === 0 ? 'text-ink' : 'text-amber-600' }}">{{ $summary['compared']['gap'] > 0 ? '+' : '' }}{{ number_format($summary['compared']['gap'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-ink-muted">variables et prêts inclus</p>
                    </div>
                    <div class="rounded-lg border border-line p-3">
                        <p class="text-xs uppercase tracking-wide text-ink-muted">Coût employeur</p>
                        <p class="mt-1 text-lg font-semibold text-ink">{{ number_format((int) $payslips->sum('employer_cost'), 0, ',', ' ') }}</p>
                        <p class="text-xs text-ink-muted">net total à payer : {{ number_format($summary['net_total'], 0, ',', ' ') }}</p>
                    </div>
                </div>

                <div class="rounded-lg bg-surface-muted px-4 py-3 text-xs text-ink-muted">
                    <p><strong class="text-ink">À vérifier</strong> : le net du contrat (mois complet, sans variables) diffère du net connu au-delà de la tolérance. C'est le signe d'une augmentation non enregistrée, d'une règle modifiée ou d'un net périmé.</p>
                    <p class="mt-1"><strong class="text-ink">Expliqué par les variables</strong> : le net du mois diffère à cause d'une absence, d'une prime, d'un prêt ou d'un prorata. Pas d'alerte.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <select wire:model.live="statusFilter" class="input w-56">
                        <option value="">Tous les bulletins</option>
                        <option value="to_check">À vérifier</option>
                        <option value="variables">Expliqués par les variables</option>
                        <option value="ok">Conformes</option>
                        <option value="no_reference">Net connu absent</option>
                    </select>
                    @if ($canTolerance)
                        <div class="flex items-center gap-2 text-sm text-ink-muted">
                            <label for="tol">Tolérance</label>
                            <input id="tol" type="text" inputmode="numeric" wire:model="toleranceInput" class="input w-24 text-right">
                            <span>FCFA</span>
                            <button type="button" wire:click="saveTolerance" class="rounded-lg border border-line px-3 py-1.5 text-sm text-ink">Enregistrer</button>
                        </div>
                    @endif
                </div>

                @if (! empty($stored['warnings']))
                    <div class="space-y-1 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                        <p class="font-medium">Avertissements du calcul</p>
                        @foreach ($stored['warnings'] as $w)
                            <p class="text-xs">{{ $w['text'] }}{{ $w['count'] > 1 ? ' (' . $w['count'] . ' bulletins)' : '' }}</p>
                        @endforeach
                    </div>
                @endif

                @foreach ($departments as $d)
                    <div x-data="{ open: {{ $d['stats']['to_check'] > 0 ? 'true' : 'false' }} }" wire:key="dept-{{ $status }}-{{ $d['name'] }}" class="overflow-hidden rounded-lg border border-line">
                        <button type="button" @click="open = !open" class="flex w-full flex-wrap items-center justify-between gap-2 bg-surface-muted px-4 py-3 text-left">
                            <span class="text-sm font-semibold text-ink">{{ $d['name'] }} <span class="font-normal text-ink-muted">· {{ $d['stats']['count'] }} bulletin(s)</span></span>
                            <span class="flex flex-wrap gap-2 text-xs">
                                @if ($d['stats']['to_check'] > 0) <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-amber-700">{{ $d['stats']['to_check'] }} ont un net différent</span> @endif
                                @if ($d['stats']['variables'] > 0) <span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-sky-700">{{ $d['stats']['variables'] }} expliqué(s)</span> @endif
                                @if ($d['stats']['no_reference'] > 0) <span class="rounded-full bg-surface px-2 py-0.5 text-ink-muted">{{ $d['stats']['no_reference'] }} sans net connu</span> @endif
                                @if ($d['stats']['to_check'] === 0 && $d['stats']['variables'] === 0 && $d['stats']['no_reference'] === 0) <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-emerald-700">tout est conforme</span> @endif
                            </span>
                        </button>
                        <div x-show="open" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th class="px-4 py-2">Employé</th>
                                        <th class="px-4 py-2 text-right">Net connu</th>
                                        <th class="px-4 py-2 text-right">Net du contrat</th>
                                        <th class="px-4 py-2 text-right">Net à payer</th>
                                        <th class="px-4 py-2">Contrôle</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @forelse ($d['payslips'] as $p)
                                        <tr wire:key="ps-{{ $p->id }}">
                                            <td class="px-4 py-3 text-ink">
                                                {{ $p->employee_name }}
                                                <span class="block text-xs text-ink-muted">{{ $p->matricule }}</span>
                                                <a href="{{ route('payroll.simulation', ['payslip' => $p->id]) }}" class="text-xs text-ink underline">Détail du calcul</a>
                                            </td>
                                            <td class="px-4 py-3 text-right text-ink">{{ $p->net_known !== null ? number_format($p->net_known, 0, ',', ' ') : '—' }}</td>
                                            <td class="px-4 py-3 text-right text-ink-muted">{{ number_format($p->net_contract, 0, ',', ' ') }}</td>
                                            <td class="px-4 py-3 text-right font-medium text-ink">{{ number_format($p->net_to_pay, 0, ',', ' ') }}</td>
                                            <td class="px-4 py-3">
                                                @if ($p->check_status === 'ok')
                                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Conforme</span>
                                                @elseif ($p->check_status === 'variables')
                                                    <span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-xs text-sky-700">Expliqué par les variables</span>
                                                    <span class="block text-xs text-ink-muted">effet sur le net : {{ $p->variables_effect > 0 ? '+' : '' }}{{ number_format($p->variables_effect, 0, ',', ' ') }}</span>
                                                @elseif ($p->check_status === 'no_reference')
                                                    <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Net connu absent</span>
                                                @else
                                                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">À vérifier</span>
                                                    <span class="block text-xs text-ink-muted">écart de structure : {{ $p->structure_gap > 0 ? '+' : '' }}{{ number_format($p->structure_gap, 0, ',', ' ') }}</span>
                                                    @if ($p->acknowledged_at)
                                                        <span class="block text-xs text-emerald-700">
                                                            Écart accepté par {{ $users[$p->acknowledged_by] ?? '—' }} le {{ $p->acknowledged_at->format('d/m/Y') }}{{ $p->acknowledged_note ? ' : ' . $p->acknowledged_note : '' }}
                                                        </span>
                                                        @if ($canCalculate)
                                                            <button type="button" wire:click="unacknowledge({{ $p->id }})" class="text-xs text-ink-muted underline">Annuler l'acceptation</button>
                                                        @endif
                                                    @elseif ($canCalculate)
                                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                                            <input type="text" wire:model="ackNotes.{{ $p->id }}" class="input w-48" placeholder="Motif (facultatif)">
                                                            <button type="button" wire:click="acknowledge({{ $p->id }})" class="rounded-lg border border-line px-2 py-1 text-xs text-ink">Accepter l'écart</button>
                                                            <a href="{{ route('payroll.salaries') }}" class="text-xs text-ink underline">Corriger le salaire</a>
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-4 py-4 text-center text-sm text-ink-muted">Aucun bulletin dans ce filtre.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Étape 4 : clôturer --}}
    @if ($status !== 'draft')
        <section x-data="{ open: true }" wire:key="step4-{{ $status }}" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">4. {{ $status === 'closed' ? 'Paie clôturée' : 'Clôturer' }}</span>
                <span class="text-xs text-ink-muted">{{ $payslips->count() }} bulletin(s)</span>
            </button>
            <div x-show="open" class="space-y-4 px-5 pb-5">
                @if ($status === 'calculated')
                    <p class="text-sm text-ink-muted">
                        La clôture rend les bulletins <strong>définitifs</strong> : leurs valeurs sont figées, les échéances de prêts du mois sont marquées prélevées et les variables du mois sont verrouillées. Une erreur se corrige ensuite par une régularisation tracée, jamais en rouvrant la paie.
                    </p>
                    @if ($pending > 0)
                        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                            {{ $pending }} bulletin(s) ont un net différent du net connu et attendent une décision : corrigez le salaire, ou acceptez l'écart avec un motif (étape 3).
                        </div>
                    @endif
                    @if ($canCalculate)
                        <label class="flex items-start gap-2 text-sm text-ink">
                            <input type="checkbox" wire:model="confirmClose" class="mt-0.5">
                            <span>J'ai vérifié les écarts et je clôture la paie de {{ $monthLabel }}.</span>
                        </label>
                        <button type="button" wire:click="closePeriod" wire:loading.attr="disabled" @disabled($pending > 0) class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface disabled:opacity-50">
                            <span wire:loading.remove wire:target="closePeriod">Clôturer la paie</span>
                            <span wire:loading wire:target="closePeriod">Vérification et clôture…</span>
                        </button>
                    @endif
                @else
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-line p-3"><p class="text-xs uppercase tracking-wide text-ink-muted">Brut</p><p class="mt-1 text-lg font-semibold text-ink">{{ number_format((int) $payslips->sum('gross'), 0, ',', ' ') }}</p></div>
                        <div class="rounded-lg border border-line p-3"><p class="text-xs uppercase tracking-wide text-ink-muted">Net à payer</p><p class="mt-1 text-lg font-semibold text-ink">{{ number_format((int) $payslips->sum('net_to_pay'), 0, ',', ' ') }}</p></div>
                        <div class="rounded-lg border border-line p-3"><p class="text-xs uppercase tracking-wide text-ink-muted">Coût employeur</p><p class="mt-1 text-lg font-semibold text-ink">{{ number_format((int) $payslips->sum('employer_cost'), 0, ',', ' ') }}</p></div>
                        <div class="rounded-lg border border-line p-3"><p class="text-xs uppercase tracking-wide text-ink-muted">Clôturée</p><p class="mt-1 text-sm font-medium text-ink">{{ $periodModel->closed_at?->format('d/m/Y à H:i') }}</p><p class="text-xs text-ink-muted">{{ $closer }}</p></div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-line">
                        <table class="min-w-full text-sm">
                            <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                                <tr>
                                    <th class="px-4 py-2">Employé</th>
                                    <th class="px-4 py-2">Département</th>
                                    <th class="px-4 py-2 text-right">Brut</th>
                                    <th class="px-4 py-2 text-right">Net à payer</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($payslips as $p)
                                    <tr wire:key="final-{{ $p->id }}">
                                        <td class="px-4 py-3 text-ink">{{ $p->employee_name }}<span class="block text-xs text-ink-muted">{{ $p->matricule }}</span></td>
                                        <td class="px-4 py-3 text-ink-muted">{{ $p->department_name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-right text-ink">{{ number_format($p->gross, 0, ',', ' ') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-ink">{{ number_format($p->net_to_pay, 0, ',', ' ') }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('payroll.simulation', ['payslip' => $p->id]) }}" class="text-sm text-ink underline">Détail</a>
                                            @if ($canRegularize)
                                                <button type="button" wire:click="startRegularize({{ $p->id }})" class="ml-3 text-sm text-ink underline">Régulariser</button>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($regularizeId === $p->id)
                                        <tr wire:key="reg-{{ $p->id }}">
                                            <td colspan="5" class="bg-surface-muted px-4 py-4">
                                                <p class="mb-2 text-sm font-medium text-ink">Régulariser le bulletin de {{ $p->employee_name }}</p>
                                                <p class="mb-3 text-xs text-ink-muted">Le bulletin clôturé ne change pas : la correction apparaît dans les variables du mois suivant, avec son motif et le lien vers ce bulletin.</p>
                                                <div class="flex flex-wrap items-start gap-2">
                                                    <select wire:model="regDirection" class="input w-52">
                                                        <option value="add">Verser un complément</option>
                                                        <option value="deduct">Retenir un trop-perçu</option>
                                                    </select>
                                                    <input type="text" inputmode="numeric" wire:model="regAmount" class="input w-36 text-right" placeholder="Montant (FCFA)">
                                                    <input type="text" wire:model="regReason" class="input w-72" placeholder="Motif (obligatoire)">
                                                    <button type="button" wire:click="saveRegularize" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-surface">Enregistrer</button>
                                                    <button type="button" wire:click="cancelRegularize" class="rounded-lg border border-line px-3 py-2 text-sm text-ink">Annuler</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Étape 5 : envoyer les bulletins --}}
    @if ($status === 'closed' && $deliverySummary)
        <section x-data="{ open: true }" wire:key="step5" class="rounded-xl border border-line bg-surface">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="text-sm font-semibold text-ink">5. Envoyer les bulletins</span>
                <span class="text-xs {{ $deliverySummary['failed'] + $deliverySummary['not_sent'] > 0 ? 'text-amber-600' : 'text-emerald-700' }}">
                    {{ $deliverySummary['sent'] }} / {{ $deliverySummary['total'] }} envoyé(s)
                </span>
            </button>
            <div x-show="open" class="space-y-4 px-5 pb-5">
                <p class="text-sm text-ink-muted">
                    Chaque bulletin part au <strong>e-mail professionnel</strong> et au <strong>e-mail personnel</strong> de l'employé, avec le PDF en pièce jointe, et une notification l'avertit dans l'application où il retrouve ses bulletins dans « Mes bulletins ».
                </p>

                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-emerald-700">{{ $deliverySummary['sent'] }} envoyé(s)</span>
                    @if ($deliverySummary['partial'] > 0) <span class="rounded-full bg-amber-500/15 px-3 py-1 text-amber-700">{{ $deliverySummary['partial'] }} partiel(s)</span> @endif
                    @if ($deliverySummary['failed'] > 0) <span class="rounded-full bg-red-500/15 px-3 py-1 text-red-600">{{ $deliverySummary['failed'] }} en échec</span> @endif
                    @if ($deliverySummary['not_sent'] > 0) <span class="rounded-full bg-surface-muted px-3 py-1 text-ink-muted">{{ $deliverySummary['not_sent'] }} non envoyé(s)</span> @endif
                    @if ($deliverySummary['no_address'] > 0) <span class="rounded-full bg-amber-500/15 px-3 py-1 text-amber-700">{{ $deliverySummary['no_address'] }} sans adresse e-mail</span> @endif
                    <span class="rounded-full bg-sky-500/15 px-3 py-1 text-sky-700">{{ $deliverySummary['seen_app'] }} vu(s) dans l'application</span>
                    <span class="rounded-full bg-surface-muted px-3 py-1 text-ink-muted">{{ $deliverySummary['opened_email'] }} e-mail(s) ouvert(s)</span>
                </div>

                @if ($canRegularize)
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="sendAll" wire:confirm="Envoyer à tous les employés les bulletins qui ne sont pas encore partis ?" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-surface">
                            <span wire:loading.remove wire:target="sendAll">Envoyer à tous</span>
                            <span wire:loading wire:target="sendAll">Envoi en cours…</span>
                        </button>
                        @if ($deliverySummary['failed'] + $deliverySummary['partial'] > 0)
                            <button type="button" wire:click="retryFailed" wire:loading.attr="disabled" class="rounded-lg border border-line px-4 py-2 text-sm text-ink">Relancer les échecs</button>
                        @endif
                    </div>
                @endif

                <div class="overflow-x-auto rounded-lg border border-line">
                    <table class="min-w-full text-sm">
                        <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-4 py-2">Employé</th>
                                <th class="px-4 py-2">E-mail professionnel</th>
                                <th class="px-4 py-2">E-mail personnel</th>
                                <th class="px-4 py-2">Vu</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payslips as $p)
                                <tr wire:key="send-{{ $p->id }}">
                                    <td class="px-4 py-3 text-ink">{{ $p->employee_name }}<span class="block text-xs text-ink-muted">{{ $p->matricule }}</span></td>
                                    @foreach (['work', 'personal'] as $channel)
                                        <td class="px-4 py-3">
                                            @if ($delivery[$p->id][$channel]['state'] === 'sent')
                                                <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700">Envoyé</span>
                                                <span class="block text-xs text-ink-muted">{{ $delivery[$p->id][$channel]['address'] }}</span>
                                                <span class="block text-xs text-ink-muted">{{ $delivery[$p->id][$channel]['at'] }}</span>
                                            @elseif ($delivery[$p->id][$channel]['state'] === 'failed')
                                                <span class="rounded-full bg-red-500/15 px-2 py-0.5 text-xs text-red-600">Échec</span>
                                                <span class="block text-xs text-ink-muted">{{ $delivery[$p->id][$channel]['address'] }}</span>
                                                <span class="block max-w-xs truncate text-xs text-red-600" title="{{ $delivery[$p->id][$channel]['error'] }}">{{ $delivery[$p->id][$channel]['error'] }}</span>
                                            @elseif ($delivery[$p->id][$channel]['state'] === 'not_sent')
                                                <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Non envoyé</span>
                                                <span class="block text-xs text-ink-muted">{{ $delivery[$p->id][$channel]['address'] }}</span>
                                            @else
                                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs text-amber-700">Aucune adresse</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-xs">
                                        @if ($delivery[$p->id]['viewed_at'])
                                            <span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-sky-700">Vu dans l'application</span>
                                            <span class="block text-ink-muted">{{ $delivery[$p->id]['viewed_at']->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-ink-muted">Pas encore consulté</span>
                                        @endif
                                        @if ($delivery[$p->id]['opened_at'])
                                            <span class="mt-1 block text-ink-muted" title="Indicatif : certains clients de messagerie bloquent les images ou les chargent d'avance.">E-mail ouvert le {{ $delivery[$p->id]['opened_at'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('payslips.pdf', $p) }}" target="_blank" rel="noopener" class="text-sm text-ink underline">PDF</a>
                                        @if ($canRegularize)
                                            <button type="button" wire:click="sendOne({{ $p->id }})" wire:loading.attr="disabled" class="ml-3 rounded-lg border border-line px-3 py-1.5 text-sm text-ink">
                                                {{ $delivery[$p->id]['overall'] === 'sent' ? 'Renvoyer' : 'Envoyer' }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-ink-muted">« Vu dans l'application » est le signal fiable. « E-mail ouvert » est indicatif : certains clients de messagerie bloquent les images ou les chargent d'avance.</p>
            </div>
        </section>
    @endif
</div>
