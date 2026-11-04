<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\SubscriptionNotice;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * @param array $opts discount_percent, price_override, trial_days, starts_at (Y-m-d), notes
     */
    public function subscribe(Company $company, Plan $plan, int $months, array $opts, User $by): Subscription
    {
        abort_unless($by->platformCan('subscriptions.manage'), 403);

        if (! array_key_exists($months, config('platform.periods'))) {
            throw ValidationException::withMessages(['sub' => 'Période de facturation invalide.']);
        }

        $plan->loadMissing('prices');
        $list = $plan->priceFor($months);
        $override = isset($opts['price_override']) && $opts['price_override'] !== '' ? (int) $opts['price_override'] : null;

        if ($list === null && $override === null) {
            throw ValidationException::withMessages(['sub' => 'Aucun prix défini pour cette formule et cette période : renseignez-le dans « Formules », ou saisissez un prix manuel.']);
        }

        $discount = max(0, min(100, (int) ($opts['discount_percent'] ?? 0)));
        $net = $override ?? PlanPricing::net((int) $list, $discount);
        $trialDays = (int) ($opts['trial_days'] ?? 0);
        $start = ! empty($opts['starts_at']) ? CarbonImmutable::parse($opts['starts_at']) : CarbonImmutable::today();

        $sub = DB::transaction(function () use ($company, $plan, $months, $list, $override, $discount, $net, $trialDays, $start, $opts, $by) {
            Subscription::where('company_id', $company->id)->whereNotIn('status', ['replaced', 'cancelled', 'expired'])->update(['status' => 'replaced', 'ends_at' => today()]);

            return Subscription::create([
                'company_id' => $company->id, 'plan_id' => $plan->id, 'period_months' => $months,
                'status' => $trialDays > 0 ? 'trial' : 'pending',
                'starts_at' => $start->toDateString(),
                'trial_ends_at' => $trialDays > 0 ? $start->addDays($trialDays)->toDateString() : null,
                'ends_at' => $trialDays > 0 ? $start->addDays($trialDays)->toDateString() : null,
                'list_price' => $override === null ? (int) $list : $net, 'discount_percent' => $override === null ? $discount : 0,
                'price' => $net, 'currency' => $company->currency ?: 'XOF', 'notes' => $opts['notes'] ?? null, 'created_by' => $by->id,
            ]);
        });

        PlanGate::flush($company->id);
        PlatformAudit::log('subscription_created', "Abonnement {$plan->name} ({$months} mois) créé pour {$company->name}", $company->id, ['price' => $net, 'trial_days' => $trialDays]);

        return $sub;
    }

    /** @param array $d amount, paid_at (Y-m-d), method, reference, note */
    public function recordPayment(Subscription $sub, array $d, User $by): SubscriptionPayment
    {
        abort_unless($by->platformCan('payments.record'), 403);

        $amount = (int) ($d['amount'] ?? 0);
        $method = (string) ($d['method'] ?? '');
        $reference = trim((string) ($d['reference'] ?? ''));

        if ($amount < 1) {
            throw ValidationException::withMessages(['pay' => 'Saisissez le montant encaissé.']);
        }

        if (! array_key_exists($method, config('platform.payment_methods'))) {
            throw ValidationException::withMessages(['pay' => 'Choisissez le mode de paiement.']);
        }

        if ($method !== 'cash' && $reference === '') {
            throw ValidationException::withMessages(['pay' => 'La référence de la transaction est obligatoire pour ce mode de paiement.']);
        }

        try {
            $paidAt = CarbonImmutable::parse((string) ($d['paid_at'] ?? ''));
        } catch (\Throwable) {
            throw ValidationException::withMessages(['pay' => 'Date de paiement invalide.']);
        }

        if ($paidAt->startOfDay()->gt(today())) {
            throw ValidationException::withMessages(['pay' => 'La date de paiement ne peut pas être dans le futur.']);
        }

        $payment = DB::transaction(function () use ($sub, $amount, $method, $reference, $paidAt, $d, $by) {
            $s = Subscription::whereKey($sub->id)->lockForUpdate()->firstOrFail();

            if (in_array($s->status, ['cancelled', 'replaced'], true)) {
                throw ValidationException::withMessages(['pay' => 'Cet abonnement n’accepte plus de paiement : utilisez l’abonnement courant.']);
            }

            if ((int) $s->price < 1) {
                throw ValidationException::withMessages(['pay' => 'Le prix de cet abonnement est nul : il n’y a rien à encaisser.']);
            }

            // Contrôles : période déjà réglée, montant au-delà du reste à payer, limite de tranches.
            $info = $this->cycleInfo($s);
            $max = $info['max'];

            if ($info['blocked']) {
                throw ValidationException::withMessages(['pay' => $info['blocked']]);
            }

            if ($amount > $info['remaining']) {
                throw ValidationException::withMessages(['pay' => 'Le montant dépasse le reste à payer ('.PlanPricing::fcfa($info['remaining']).'). Saisissez au maximum ce montant.']);
            }

            if ($info['next_tranche'] > $max) {
                throw ValidationException::withMessages(['pay' => "Un abonnement ne peut pas être réglé en plus de {$max} tranches."]);
            }

            if ($info['next_tranche'] === $max && $amount < $info['remaining']) {
                throw ValidationException::withMessages(['pay' => "C’est la dernière tranche autorisée ({$max} sur {$max}) : elle doit solder le reste à payer, soit ".PlanPricing::fcfa($info['remaining']).'.']);
            }

            $balance = $info['remaining'] - $amount;
            $periodStart = $periodEnd = null;

            // L'abonnement n'est prolongé d'une période que lorsqu'il est SOLDÉ.
            if ($balance === 0) {
                $base = ($s->status === 'active' && $s->ends_at && $s->ends_at->gte(today())) ? CarbonImmutable::parse($s->ends_at) : CarbonImmutable::today();
                $periodStart = $base;
                $periodEnd = $base->addMonthsNoOverflow($s->period_months);

                $s->update(['status' => 'active', 'starts_at' => $s->starts_at ?? $periodStart->toDateString(), 'ends_at' => $periodEnd->toDateString()]);
                Company::whereKey($s->company_id)->where('status', 'trial')->update(['status' => 'active']);
            }

            return $this->createPayment($s, $amount, $paidAt, $method, $reference, $periodStart, $periodEnd, $d['note'] ?? null, $by, [
                'cycle' => $info['cycle'], 'tranche' => $info['next_tranche'], 'balance_after' => $balance,
            ]);
        });

        PlanGate::flush($sub->company_id);
        $this->notifyPayment($sub, $payment);
        PlatformAudit::log('payment_recorded', 'Paiement de '.PlanPricing::fcfa($amount)." enregistré ({$payment->invoice_number})", $sub->company_id, ['method' => $method]);

        return $payment;
    }

    /**
     * État du règlement d'un abonnement : cycle en cours, déjà payé, reste à payer, rang de la prochaine tranche.
     * « blocked » = raison pour laquelle aucun paiement n'est accepté pour l'instant (null sinon).
     * Un abonnement soldé n'accepte un nouveau paiement (renouvellement) que dans la fenêtre précédant son échéance.
     */
    public function cycleInfo(Subscription $s): array
    {
        $max = (int) config('platform.max_tranches', 3);
        $price = (int) $s->price;
        $payments = SubscriptionPayment::where('subscription_id', $s->id)->get(['cycle', 'amount']);
        $cycle = max(1, (int) $payments->max('cycle'));
        $paid = (int) $payments->where('cycle', $cycle)->sum('amount');
        $tranches = $payments->where('cycle', $cycle)->count();
        $settled = $price > 0 && $paid >= $price;
        $blocked = null;

        if ($settled) {
            $window = (int) config('platform.renewal_window_days', 30);

            if ($s->ends_at && $s->ends_at->gt(today()->addDays($window))) {
                $blocked = 'Cet abonnement est déjà réglé jusqu’au '.$s->ends_at->format('d/m/Y').'. Le renouvellement s’ouvre '.$window.' jours avant l’échéance.';
            }

            $info = ['settled' => true, 'paid_last' => $paid, 'tranches_last' => $tranches, 'cycle' => $cycle + 1, 'paid' => 0, 'tranches' => 0];
        } else {
            $info = ['settled' => false, 'paid_last' => $paid, 'tranches_last' => $tranches, 'cycle' => $cycle, 'paid' => $paid, 'tranches' => $tranches];
        }

        return $info + ['price' => $price, 'remaining' => max(0, $price - $info['paid']), 'next_tranche' => $info['tranches'] + 1, 'max' => $max, 'blocked' => $blocked];
    }

    private function createPayment(Subscription $s, int $amount, CarbonImmutable $paidAt, string $method, string $reference, ?CarbonImmutable $start, ?CarbonImmutable $end, ?string $note, User $by, array $extra = []): SubscriptionPayment
    {
        for ($try = 0; $try < 5; $try++) {
            $year = now()->year;
            $n = SubscriptionPayment::where('invoice_number', 'like', "FAC-{$year}-%")->count() + 1 + $try;

            try {
                return SubscriptionPayment::create([
                    'subscription_id' => $s->id, 'company_id' => $s->company_id,
                    'invoice_number' => sprintf('FAC-%d-%04d', $year, $n), 'amount' => $amount, 'paid_at' => $paidAt->toDateString(),
                    'method' => $method, 'reference' => $reference ?: null, 'period_start' => $start?->toDateString(), 'period_end' => $end?->toDateString(),
                    'note' => $note ? trim($note) : null, 'recorded_by' => $by->id,
                ] + $extra);
            } catch (UniqueConstraintViolationException) {
                // deux encaissements simultanés : on réessaie avec le numéro suivant
            }
        }

        throw ValidationException::withMessages(['pay' => 'Impossible de numéroter la facture, réessayez.']);
    }

    public function cancel(Subscription $sub, string $reason, User $by): void
    {
        abort_unless($by->platformCan('subscriptions.manage'), 403);

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['sub' => 'Indiquez le motif de l’annulation (5 caractères minimum).']);
        }

        $sub->update(['status' => 'cancelled', 'ends_at' => today(), 'notes' => trim(($sub->notes ? $sub->notes."\n" : '').'Annulé : '.$reason)]);
        PlanGate::flush($sub->company_id);
        PlatformAudit::log('subscription_cancelled', "Abonnement annulé : {$reason}", $sub->company_id);
    }

    /** Tâche quotidienne : fait avancer les statuts, prévient les propriétaires (notification interne + e-mail) avant et après l'échéance. */
    public function syncStatuses(): array
    {
        $stats = ['past_due' => 0, 'expired' => 0, 'notices' => 0, 'emails' => 0];
        $grace = (int) config('platform.grace_days', 15);
        $pending = (int) config('platform.pending_days', 7);
        $mailer = app(SubscriptionMailer::class);

        $current = Subscription::query()->whereIn('id', Subscription::selectRaw('max(id)')->groupBy('company_id'))->whereIn('status', ['trial', 'pending', 'active', 'past_due'])->get();

        foreach ($current as $s) {
            $new = match (true) {
                $s->status === 'trial' && $s->trial_ends_at && $s->trial_ends_at->lt(today()) => 'expired',
                $s->status === 'pending' && $this->pendingExpired($s, $pending) => 'expired',
                $s->status === 'active' && $s->ends_at && $s->ends_at->lt(today()) => 'past_due',
                $s->status === 'past_due' && $s->ends_at && $s->ends_at->lt(today()->subDays($grace)) => 'expired',
                default => null,
            };

            if ($new) {
                $s->update(['status' => $new]);
                PlanGate::flush($s->company_id);
                $stats[$new === 'expired' ? 'expired' : 'past_due']++;
                PlatformAudit::log('subscription_'.$new, "Abonnement passé en « ".Subscription::STATUSES[$new]." »", $s->company_id);
                $this->notifyOwners($s, $new === 'expired' ? 'Abonnement expiré' : 'Paiement de l’abonnement en retard',
                    $new === 'expired' ? 'Votre abonnement a expiré : certaines fonctions sont bloquées. Contactez-nous pour le renouveler.' : 'L’échéance de votre abonnement est dépassée. Régularisez-la pour éviter une interruption.');

                if ($mailer->sendNotice($s->fresh(), $new === 'expired' ? 'expired' : 'past_due')) {
                    $stats['emails']++;
                }

                continue;
            }

            $type = $this->reminderType($s);

            if ($type && $mailer->sendNotice($s, $type)) {
                $stats['emails']++;
                $stats['notices']++;
                $end = str_starts_with($type, 'trial_') ? $s->trial_ends_at : $s->ends_at;
                $this->notifyOwners($s, str_starts_with($type, 'trial_') ? 'Votre essai se termine bientôt' : 'Votre abonnement arrive à échéance', 'Échéance le '.$end?->format('d/m/Y').'. Pensez à le renouveler pour éviter toute interruption.');
            }
        }

        return $stats;
    }

    /** Sans aucun paiement : expire après « pending_days » jours. Réglé en partie : les tranches doivent être soldées dans « installment_days » jours (45 par défaut) après la première. */
    private function pendingExpired(Subscription $s, int $pendingDays): bool
    {
        $first = SubscriptionPayment::where('subscription_id', $s->id)->min('created_at');

        if (! $first) {
            return $s->created_at->lt(now()->subDays($pendingDays));
        }

        return \Illuminate\Support\Carbon::parse($first)->lt(now()->subDays((int) config('platform.installment_days', 45)));
    }

    /** Palier de rappel à envoyer aujourd'hui : le plus proche de l'échéance (30, 7, 1 jours ; essai : 7, 3, 1). Un seul rappel par échéance et par palier. */
    private function reminderType(Subscription $s): ?string
    {
        $isTrial = $s->status === 'trial';
        $end = $isTrial ? $s->trial_ends_at : ($s->status === 'active' ? $s->ends_at : null);

        if (! $end) {
            return null;
        }

        $days = (int) today()->diffInDays($end, false);

        if ($days < 0) {
            return null;
        }

        $step = collect($isTrial ? [1, 3, 7] : [1, 7, 30])->first(fn ($t) => $days <= $t);

        return $step === null ? null : ($isTrial ? 'trial_' : 'reminder_').$step;
    }

    /** Notification dans l'application : le propriétaire de l'entreprise est prévenu du paiement et retrouve sa facture dans « Mon abonnement ». */
    private function notifyPayment(Subscription $sub, SubscriptionPayment $payment): void
    {
        $sub->refresh();
        $extended = $payment->period_end ? ' Votre abonnement est actif jusqu’au '.$payment->period_end->format('d/m/Y').'.' : ' Il reste '.PlanPricing::fcfa((int) $payment->balance_after).' à régler (tranche '.$payment->tranche.' sur '.(int) config('platform.max_tranches', 3).').';
        $this->notifyOwners($sub, 'Paiement reçu : merci !', 'Nous avons enregistré votre paiement de '.PlanPricing::fcfa((int) $payment->amount)." (facture {$payment->invoice_number}).{$extended} Retrouvez votre facture dans « Mon abonnement ».");
    }

    private function notifyOwners(Subscription $s, string $title, string $message): void
    {
        $owners = User::where('company_id', $s->company_id)->where('is_company_owner', true)->get();

        if ($owners->isNotEmpty()) {
            Notification::send($owners, new SubscriptionNotice($title, $message, \Illuminate\Support\Facades\Route::has('subscription.mine') ? route('subscription.mine', [], false) : null));
        }
    }
}
