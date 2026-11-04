@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' FCFA';
    $statusLabels = ['trial' => 'Essai', 'active' => 'Actif', 'suspended' => 'Suspendu'];
    $subBadge = ['trial' => 'bg-sky-100 text-sky-800', 'pending' => 'bg-amber-100 text-amber-900', 'active' => 'bg-emerald-100 text-emerald-800', 'past_due' => 'bg-rose-100 text-rose-800', 'suspended' => 'bg-rose-100 text-rose-800', 'expired' => 'bg-slate-200 text-slate-700', 'cancelled' => 'bg-slate-200 text-slate-700', 'replaced' => 'bg-slate-100 text-slate-600'];
    $selPlan = $plans->firstWhere('id', (int) ($sub['plan_id'] ?? 0));
    $list = $selPlan?->priceFor((int) ($sub['months'] ?? 12));
    $net = $list !== null ? \App\Services\PlanPricing::net($list, (int) ($sub['discount_percent'] ?? 0)) : null;
    $usagePct = $max ? min(100, round($employees / $max * 100)) : null;
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">{{ $company->name }}</h2>
            <p class="text-sm text-ink-muted">{{ config('platform.sectors.'.$company->sector, 'Secteur non renseigné') }} · {{ config('platform.countries.'.$company->country, $company->country) }} · créée le {{ $company->created_at->format('d/m/Y') }} · {{ $company->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $company->status === 'suspended' ? 'bg-rose-100 text-rose-800' : ($company->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800') }}">{{ $statusLabels[$company->status] ?? $company->status }}</span>
            @if ($can['manage'])<a href="{{ route('platform.companies.edit', $company->id) }}" wire:navigate class="rounded-xl border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Modifier</a>@endif
        </div>
    </div>

    @if (session('status'))<p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @if (session('temp_password'))
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Identifiants du propriétaire : à transmettre maintenant, ils ne seront plus affichés.</p>
            <p class="mt-1">E-mail : <strong>{{ session('temp_password')['email'] }}</strong> · Mot de passe provisoire : <strong class="font-mono">{{ session('temp_password')['password'] }}</strong></p>
        </div>
    @endif
    @error('form')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror

    @if ($company->status === 'suspended')
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">Suspendue le {{ $company->suspended_at?->format('d/m/Y') }} : {{ $company->suspension_reason }}</p>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Abonnement courant --}}
        <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-semibold">Abonnement</h3>
            @if ($current)
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-sm font-semibold text-white" style="background: {{ $current->plan?->color }}">{{ $current->plan?->name }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $subBadge[$current->status] ?? '' }}">{{ $current->statusLabel() }}</span>
                    <span class="text-sm text-ink-muted">{{ config('platform.periods.'.$current->period_months) }} · {{ $fcfa($current->price) }}@if ($current->discount_percent) (remise {{ $current->discount_percent }} %)@endif</span>
                </div>
                <p class="text-sm text-ink-muted">Du {{ $current->starts_at?->format('d/m/Y') ?? '—' }} au <strong class="text-ink">{{ $current->ends_at?->format('d/m/Y') ?? '—' }}</strong>@if ($current->status === 'trial') (essai gratuit)@endif</p>
                @if ($usagePct !== null)
                    <div>
                        <div class="mb-1 flex justify-between text-xs"><span>Effectif actif</span><span><strong>{{ $employees }}</strong> / {{ $max }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full {{ $usagePct >= 90 ? 'bg-rose-500' : ($usagePct >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $usagePct }}%"></div></div>
                    </div>
                @else
                    <p class="text-xs text-ink-muted">Effectif actif : {{ $employees }}</p>
                @endif
            @else
                <p class="text-sm text-ink-muted">Aucun abonnement : cette entreprise n’est soumise à aucune restriction de formule.</p>
            @endif

            @if ($gate['features'] !== null && $gate['state'] !== 'unrestricted')
                <details class="rounded-xl bg-surface-muted p-3 text-sm"><summary class="cursor-pointer font-medium">Fonctionnalités actives ({{ count($gate['features']) }})</summary>
                    <ul class="mt-2 grid gap-1 sm:grid-cols-2">@foreach ($gate['features'] as $f)<li class="text-xs text-ink-muted">✔ {{ config('plans.features.'.$f.'.label', $f) }}</li>@endforeach</ul>
                </details>
            @endif

            @if ($can['subs'] && $current && ! in_array($current->status, ['cancelled', 'replaced', 'expired'], true))
                <div class="flex flex-wrap items-end gap-2 border-t border-line pt-3">
                    <input type="text" wire:model="cancelReason" class="input max-w-xs py-1.5" placeholder="Motif d’annulation">
                    <button type="button" wire:click="cancelSubscription" wire:confirm="Annuler cet abonnement ? L’entreprise perdra l’accès aux fonctions de sa formule." class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50">Annuler l’abonnement</button>
                </div>
            @endif
        </section>

        {{-- Suspension --}}
        <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">Accès de l’entreprise</h3>
            @if ($can['suspend'])
                @if ($company->status === 'suspended')
                    <button type="button" wire:click="reactivate" wire:confirm="Réactiver cette entreprise ?" class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Réactiver l’entreprise</button>
                @else
                    <input type="text" wire:model="reason" class="input py-1.5" placeholder="Motif de la suspension">
                    @error('reason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <button type="button" wire:click="suspend" wire:confirm="Suspendre cette entreprise ? Ses utilisateurs ne pourront plus travailler." class="w-full rounded-xl border border-rose-300 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">Suspendre</button>
                @endif
            @else
                <p class="text-sm text-ink-muted">Vous n’avez pas le droit de suspendre une entreprise.</p>
            @endif
        </section>
    </div>

    {{-- Nouvel abonnement --}}
    @if ($can['subs'])
        <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div><h3 class="text-sm font-semibold">{{ $current ? 'Changer de formule ou renouveler' : 'Souscrire un abonnement' }}</h3><p class="text-xs text-ink-muted">Le nouvel abonnement remplace l’actuel. Le prix est figé à la souscription ; une remise ou un prix manuel peut être appliqué.</p></div>
            @error('sub')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="mb-1 block text-xs font-medium">Formule</label><select wire:model.live="sub.plan_id" class="input py-1.5"><option value="">Choisir…</option>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium">Période</label><select wire:model.live="sub.months" class="input py-1.5">@foreach (config('platform.periods') as $m => $l)<option value="{{ $m }}">{{ $l }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium">Remise (%)</label><input type="number" min="0" max="100" wire:model.live="sub.discount_percent" class="input py-1.5"></div>
                <div><label class="mb-1 block text-xs font-medium">Prix manuel (facultatif)</label><input type="number" min="0" wire:model.live="sub.price_override" class="input py-1.5" placeholder="Remplace le prix catalogue"></div>
                <div><label class="mb-1 block text-xs font-medium">Essai gratuit (jours)</label><input type="number" min="0" max="90" wire:model="sub.trial_days" class="input py-1.5" placeholder="0 = pas d’essai"></div>
                <div><label class="mb-1 block text-xs font-medium">Début</label><input type="date" wire:model="sub.starts_at" class="input py-1.5"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Notes</label><input type="text" wire:model="sub.notes" class="input py-1.5"></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-muted">@if ($selPlan)@if (($sub['price_override'] ?? '') !== '')Prix net : <strong class="text-ink">{{ $fcfa($sub['price_override']) }}</strong>@elseif ($net !== null)Catalogue {{ $fcfa($list) }} → prix net <strong class="text-ink">{{ $fcfa($net) }}</strong>@else<span class="text-rose-600">Aucun prix défini pour cette formule et cette période.</span>@endif @endif</p>
                <button type="button" wire:click="subscribe" wire:loading.attr="disabled" wire:confirm="Créer cet abonnement ? Il remplacera l’abonnement actuel." class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Créer l’abonnement</button>
            </div>
        </section>
    @endif

    {{-- Encaissement --}}
    @if ($can['pay'] && $current && ! in_array($current->status, ['cancelled', 'replaced'], true))
        <section class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="space-y-3">
                <div><h3 class="text-sm font-semibold">Enregistrer un paiement</h3><p class="text-xs text-ink-muted">Un abonnement se règle en {{ $cycle['max'] }} tranches au maximum ; la dernière doit solder le reste. L’abonnement n’est prolongé qu’une fois soldé.</p></div>
                @if ($cycle)
                    <div class="rounded-xl bg-surface-muted p-3">
                        <div class="mb-1 flex flex-wrap justify-between gap-2 text-sm"><span>{{ $cycle['settled'] ? 'Période réglée' : ($cycle['cycle'] > 1 ? 'Renouvellement (période '.$cycle['cycle'].')' : 'Règlement en cours') }}</span><span class="font-semibold">{{ $fcfa($cycle['settled'] ? $cycle['paid_last'] : $cycle['paid']) }} / {{ $fcfa($cycle['price']) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full {{ $cycle['settled'] ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $cycle['price'] ? min(100, round(($cycle['settled'] ? $cycle['paid_last'] : $cycle['paid']) / $cycle['price'] * 100)) : 0 }}%"></div></div>
                        <p class="mt-1.5 text-xs text-ink-muted">@if ($cycle['settled'] && $cycle['blocked']){{ $cycle['blocked'] }}@elseif ($cycle['settled'])Période soldée : le prochain paiement ouvre le renouvellement ({{ $fcfa($cycle['price']) }}).@else Reste à payer : <strong class="text-ink">{{ $fcfa($cycle['remaining']) }}</strong> · prochaine tranche : {{ $cycle['next_tranche'] }} sur {{ $cycle['max'] }}@if ($cycle['next_tranche'] === $cycle['max']) (elle doit solder le reste)@endif @endif</p>
                    </div>
                @endif
            </div>
            @error('pay')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div><label class="mb-1 block text-xs font-medium">Montant (FCFA)</label><input type="number" min="1" wire:model="pay.amount" class="input py-1.5"></div>
                <div><label class="mb-1 block text-xs font-medium">Date</label><input type="date" max="{{ now()->toDateString() }}" wire:model="pay.paid_at" class="input py-1.5"></div>
                <div><label class="mb-1 block text-xs font-medium">Mode</label><select wire:model.live="pay.method" class="input py-1.5">@foreach (config('platform.payment_methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div>@if (($pay['method'] ?? '') !== 'cash')<label class="mb-1 block text-xs font-medium">Référence</label><input type="text" wire:model="pay.reference" class="input py-1.5">@endif</div>
                <div><label class="mb-1 block text-xs font-medium">Note</label><input type="text" wire:model="pay.note" class="input py-1.5"></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="pay.send_invoice" class="mt-0.5"><span>Envoyer la facture par e-mail<span class="block text-xs text-ink-muted">@if ($recipients)À : {{ implode(', ', $recipients) }}@else<span class="text-amber-700">Aucune adresse e-mail valide : renseignez l’e-mail de l’entreprise.</span>@endif</span></span></label>
                <button type="button" wire:click="recordPayment" wire:loading.attr="disabled" @disabled($cycle && $cycle['blocked']) wire:confirm="Enregistrer ce paiement ?" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60">Enregistrer le paiement</button>
            </div>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Derniers paiements</h3>
            <ul class="divide-y divide-line text-sm">
                @forelse ($payments as $p)
                    <li class="space-y-1 py-2" wire:key="pm-{{ $p->id }}">
                        <div class="flex flex-wrap justify-between gap-2">
                            <span><span class="font-mono text-xs">{{ $p->invoice_number }}</span> · {{ $p->paid_at->format('d/m/Y') }}@if ($p->tranche)<span class="ml-1 rounded-full bg-surface-muted px-2 py-0.5 text-xs">tranche {{ $p->tranche }}/{{ config('platform.max_tranches', 3) }}</span>@endif<span class="block text-xs text-ink-muted">{{ config('platform.payment_methods.'.$p->method) }}@if ($p->reference) · {{ $p->reference }}@endif @if ($p->period_end) · couvre jusqu’au {{ $p->period_end->format('d/m/Y') }}@endif</span></span>
                            <span class="text-right"><strong>{{ $fcfa($p->amount) }}</strong>@if ((int) $p->balance_after > 0)<span class="block text-xs text-amber-700">reste {{ $fcfa($p->balance_after) }}</span>@elseif ($p->tranche > 1)<span class="block text-xs text-emerald-700">soldé</span>@endif</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <x-document-actions :view="route('platform.invoice', $p->id)" :download="route('platform.invoice', [$p->id, 'dl' => 1])" ext="pdf" :name="'Facture '.$p->invoice_number" />
                            @if ($can['pay'])<button type="button" wire:click="resendInvoice({{ $p->id }})" wire:loading.attr="disabled" wire:target="resendInvoice({{ $p->id }})" class="rounded border border-line px-2 py-1 hover:bg-surface-muted disabled:opacity-60">{{ $p->invoice_sent_at ? 'Renvoyer par e-mail' : 'Envoyer par e-mail' }}</button>@endif
                            @if ($p->invoice_sent_at)<span class="text-ink-muted">Envoyée le {{ $p->invoice_sent_at->format('d/m/Y H:i') }} à {{ $p->invoice_sent_to }}</span>@else<span class="text-amber-700">Non envoyée</span>@endif
                        </div>
                    </li>
                @empty<li class="py-6 text-center text-ink-muted">Aucun paiement.</li>@endforelse
            </ul>
        </section>
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Historique des abonnements</h3>
            <ul class="divide-y divide-line text-sm">
                @forelse ($history as $h)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2"><span>{{ $h->plan?->name }} · {{ config('platform.periods.'.$h->period_months) }}<span class="block text-xs text-ink-muted">{{ $h->starts_at?->format('d/m/Y') ?? '—' }} → {{ $h->ends_at?->format('d/m/Y') ?? '—' }} · {{ $fcfa($h->price) }}</span></span><span class="rounded-full px-2 py-0.5 text-xs {{ $subBadge[$h->status] ?? '' }}">{{ $h->statusLabel() }}</span></li>
                @empty<li class="py-6 text-center text-ink-muted">Aucun abonnement.</li>@endforelse
            </ul>
        </section>
    </div>

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-1 text-sm font-semibold">E-mails envoyés à cette entreprise</h3>
        <p class="mb-3 text-xs text-ink-muted">Destinataires : @if ($recipients){{ implode(', ', $recipients) }}@else<span class="text-amber-700">aucune adresse valide (e-mail de l’entreprise ou propriétaire à renseigner)</span>@endif. Les rappels partent automatiquement 30, 7 et 1 jours avant l’échéance, puis en cas de retard ou d’expiration.</p>
        <ul class="divide-y divide-line text-sm">
            @forelse ($mailLogs as $l)
                <li class="flex flex-wrap items-start justify-between gap-2 py-2" wire:key="mlg-{{ $l->id }}">
                    <span>{{ $l->typeLabel() }}<span class="block text-xs text-ink-muted">{{ $l->created_at->format('d/m/Y H:i') }} · {{ $l->triggered_by ? 'par '.$l->triggeredBy?->name : 'automatique' }}</span>@if ($l->status === 'failed')<span class="block text-xs text-rose-700">{{ $l->error }}</span>@endif</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $l->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $l->status === 'sent' ? 'Envoyé' : 'Échec' }}</span>
                </li>
            @empty
                <li class="py-6 text-center text-ink-muted">Aucun e-mail envoyé.</li>
            @endforelse
        </ul>
    </section>

    <a href="{{ route('platform.companies') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Retour à la liste</a>
</div>
