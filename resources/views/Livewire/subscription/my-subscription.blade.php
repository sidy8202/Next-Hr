@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ').' '.(in_array($sub?->currency, ['XOF', 'XAF', null], true) ? 'FCFA' : $sub->currency);
    $badge = ['trial' => 'bg-sky-100 text-sky-800', 'pending' => 'bg-amber-100 text-amber-900', 'active' => 'bg-emerald-100 text-emerald-800', 'past_due' => 'bg-rose-100 text-rose-800', 'suspended' => 'bg-rose-100 text-rose-800', 'expired' => 'bg-slate-200 text-slate-700', 'cancelled' => 'bg-slate-200 text-slate-700'];
    $end = $sub ? ($sub->status === 'trial' ? $sub->trial_ends_at : $sub->ends_at) : null;
    $days = $end ? (int) today()->diffInDays($end, false) : null;
    $included = $sub?->plan ? $sub->plan->features->where('enabled', true)->pluck('feature')->all() : [];
@endphp

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Mon abonnement</h2>
        <p class="text-sm text-ink-muted">Votre formule, son échéance, ce qu’elle inclut et vos factures.</p>
    </div>

    @if ($state === 'locked')
        <p class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">Votre accès est limité : l’abonnement est expiré ou suspendu. Vos données sont conservées et seront de nouveau accessibles dès le règlement. Voir « Comment régler » plus bas.</p>
    @elseif ($state === 'grace')
        <p class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">L’échéance de votre abonnement est dépassée. Régularisez le paiement pour éviter une interruption du service.</p>
    @endif

    @if ($cycle && ! $cycle['settled'] && $cycle['paid'] > 0)
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:bg-amber-500/10">
            <h3 class="text-sm font-semibold">Règlement en cours</h3>
            <p class="mt-1 text-sm">Vous avez réglé <strong>{{ number_format($cycle['paid'], 0, ',', ' ') }} FCFA</strong> sur {{ number_format($cycle['price'], 0, ',', ' ') }} FCFA ({{ $cycle['tranches'] }} tranche(s) sur {{ $cycle['max'] }} au maximum). Reste à payer : <strong>{{ number_format($cycle['remaining'], 0, ',', ' ') }} FCFA</strong>.</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-black/5"><div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, round($cycle['paid'] / max(1, $cycle['price']) * 100)) }}%"></div></div>
        </section>
    @endif

    @if (! $sub)
        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm"><p class="text-sm text-ink-muted">Aucun abonnement n’est associé à votre entreprise : vous bénéficiez de toutes les fonctionnalités. Pour toute question, contactez {{ $contact->legal_name }}.</p></section>
    @else
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="space-y-3 rounded-2xl border border-line bg-surface p-5 shadow-sm lg:col-span-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-sm font-semibold text-white" style="background: {{ $sub->plan?->color }}">{{ $sub->plan?->name }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $badge[$sub->status] ?? '' }}">{{ $sub->statusLabel() }}</span>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-ink-muted">Période de facturation</dt><dd class="font-medium">{{ config('platform.periods.'.$sub->period_months) }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">Montant par période</dt><dd class="font-medium">{{ $fcfa($sub->price) }}</dd></div>
                    <div><dt class="text-xs text-ink-muted">{{ $sub->status === 'trial' ? 'Fin de l’essai' : 'Échéance' }}</dt><dd class="font-medium">{{ $end?->format('d/m/Y') ?? '—' }}@if ($days !== null && $days >= 0 && $days <= 30) <span class="text-xs {{ $days <= 7 ? 'text-rose-600' : 'text-amber-700' }}">(dans {{ $days }} j)</span>@endif</dd></div>
                </dl>
                @if ($usage !== null)
                    <div>
                        <div class="mb-1 flex justify-between text-xs"><span>Effectif actif</span><span><strong>{{ $employees }}</strong> / {{ $max }} employés</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full {{ $usage >= 90 ? 'bg-rose-500' : ($usage >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $usage }}%"></div></div>
                        @if ($usage >= 90)<p class="mt-1 text-xs text-rose-600">Vous approchez du plafond de votre formule : contactez-nous pour passer à la formule supérieure.</p>@endif
                    </div>
                @endif
            </section>

            <section class="space-y-2 rounded-2xl border border-line bg-surface p-5 text-sm shadow-sm">
                <h3 class="text-sm font-semibold">Comment régler</h3>
                @if ($contact->bank_details)<p class="whitespace-pre-line text-ink-muted">{{ $contact->bank_details }}</p>@else<p class="text-ink-muted">Contactez-nous pour connaître les moyens de règlement.</p>@endif
                <p class="text-xs text-ink-muted">{{ $contact->legal_name }}@if ($contact->phone) · {{ $contact->phone }}@endif @if ($contact->email) · {{ $contact->email }}@endif</p>
            </section>
        </div>

        <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold">Ce que comprend votre formule</h3>
            <div class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                @foreach ($features as $module => $list)
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $module }}</p>
                        <ul class="space-y-1 text-sm">
                            @foreach ($list as $key => $f)
                                <li class="flex gap-2 {{ in_array($key, $included, true) ? '' : 'text-ink-muted' }}"><span>{{ in_array($key, $included, true) ? '✔' : '🔒' }}</span><span>{{ $f['label'] }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-ink-muted">Les fonctionnalités marquées d’un cadenas relèvent d’une formule supérieure.</p>
        </section>
    @endif

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold">Mes factures</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-ink-muted"><tr class="border-b border-line"><th class="py-2 pr-3 font-medium">Facture</th><th class="py-2 pr-3 font-medium">Date</th><th class="py-2 pr-3 font-medium">Période couverte</th><th class="py-2 pr-3 text-right font-medium">Montant</th><th class="py-2"></th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($payments as $p)
                        <tr wire:key="inv-{{ $p->id }}">
                            <td class="py-2 pr-3 font-mono text-xs">{{ $p->invoice_number }}</td>
                            <td class="py-2 pr-3">{{ $p->paid_at->format('d/m/Y') }}</td>
                            <td class="py-2 pr-3 text-xs text-ink-muted">@if ($p->period_start && $p->period_end){{ $p->period_start->format('d/m/Y') }} → {{ $p->period_end->format('d/m/Y') }}@else Acompte @endif</td>
                            <td class="py-2 pr-3 text-right">{{ $fcfa($p->amount) }}</td>
                            <td class="py-2 text-right"><x-document-actions :view="route('subscription.invoice', $p->id)" :download="route('subscription.invoice', [$p->id, 'dl' => 1])" ext="pdf" :name="'Facture '.$p->invoice_number" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ink-muted">Aucune facture pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
