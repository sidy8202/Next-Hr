<?php

namespace App\Services;

use App\Mail\SubscriptionInvoiceMail;
use App\Mail\SubscriptionNoticeMail;
use App\Models\Company;
use App\Models\PlatformMailLog;
use App\Models\PlatformProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tous les e-mails d'abonnement envoyés aux entreprises : factures et rappels.
 * Chaque envoi (réussi ou en échec) est tracé dans platform_mail_logs ; une panne d'e-mail ne bloque jamais l'action en cours.
 */
class SubscriptionMailer
{
    /** Adresses valides : e-mail de l'entreprise + propriétaires actifs du compte, sans doublon. */
    public function recipients(Company $company): array
    {
        $emails = collect([$company->email])
            ->merge(User::where('company_id', $company->id)->where('is_company_owner', true)->where('is_active', true)->pluck('email'))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        return $emails->unique()->values()->all();
    }

    /** @return array{ok: bool, to: array, error: ?string} */
    public function sendInvoice(SubscriptionPayment $payment, ?User $by = null): array
    {
        $payment->loadMissing(['subscription.plan', 'company']);
        $company = $payment->company;
        $to = $this->recipients($company);

        if (! $to) {
            return ['ok' => false, 'to' => [], 'error' => 'Aucune adresse e-mail valide pour cette entreprise (ni e-mail de l’entreprise, ni propriétaire).'];
        }

        $profile = PlatformProfile::current();
        [$content, $ext, $mime] = app(InvoiceRenderer::class)->render($payment, $profile);
        $mailable = new SubscriptionInvoiceMail($payment, $company, $profile, $content, $ext, $mime);

        $ok = $this->deliver($mailable, $to, 'invoice', $company, $payment->subscription_id, $payment->id, $payment->period_end?->toDateString(), $by, $err);

        if ($ok) {
            $payment->update(['invoice_sent_at' => now(), 'invoice_sent_to' => implode(', ', $to)]);
            PlatformAudit::log('invoice_sent', "Facture {$payment->invoice_number} envoyée à ".implode(', ', $to), $company->id);
        }

        return ['ok' => $ok, 'to' => $to, 'error' => $err];
    }

    /**
     * Rappel automatique (échéance, fin d'essai, retard, expiration).
     * Retourne true seulement si un e-mail est réellement parti maintenant : un rappel déjà envoyé pour la même échéance n'est jamais renvoyé.
     */
    public function sendNotice(Subscription $sub, string $type): bool
    {
        $end = str_starts_with($type, 'trial_') ? $sub->trial_ends_at : $sub->ends_at;
        $endDate = $end?->toDateString();

        $already = PlatformMailLog::where('subscription_id', $sub->id)->where('type', $type)->where('period_end', $endDate);

        if ((clone $already)->where('status', 'sent')->exists()) {
            return false;
        }

        $sub->loadMissing(['company', 'plan']);
        $company = $sub->company;
        $to = $this->recipients($company);

        if (! $to) {
            // Signalé une seule fois par échéance, pour ne pas remplir le journal chaque jour.
            if (! $already->exists()) {
                $this->log($company->id, $sub->id, null, $type, $endDate, [], SubscriptionNoticeMail::subjectFor($type), 'failed', 'Aucune adresse e-mail valide pour cette entreprise.', null);
            }

            return false;
        }

        $mailable = new SubscriptionNoticeMail($sub, $company, PlatformProfile::current(), $type);

        return $this->deliver($mailable, $to, $type, $company, $sub->id, null, $endDate, null, $err);
    }

    /** Test de la configuration d'envoi (réglages e-mail du serveur). */
    public function sendTest(string $to, User $by): array
    {
        try {
            Mail::raw('Ceci est un e-mail de test envoyé depuis votre espace plateforme Next HR. Si vous le recevez, l’envoi des factures et des rappels fonctionne.', fn ($m) => $m->to($to)->subject('Test d’envoi : Next HR'));
            $this->log(null, null, null, 'test', null, [$to], 'Test d’envoi : Next HR', 'sent', null, $by->id);

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            report($e);
            $this->log(null, null, null, 'test', null, [$to], 'Test d’envoi : Next HR', 'failed', mb_substr($e->getMessage(), 0, 500), $by->id);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function deliver(Mailable $mailable, array $to, string $type, Company $company, ?int $subscriptionId, ?int $paymentId, ?string $periodEnd, ?User $by, ?string &$error = null): bool
    {
        $subject = method_exists($mailable, 'envelope') ? ($mailable->envelope()->subject ?? '') : '';

        try {
            Mail::to($to)->send($mailable);
            $this->log($company->id, $subscriptionId, $paymentId, $type, $periodEnd, $to, $subject, 'sent', null, $by?->id);

            return true;
        } catch (Throwable $e) {
            report($e);
            $error = mb_substr($e->getMessage(), 0, 500);
            $this->log($company->id, $subscriptionId, $paymentId, $type, $periodEnd, $to, $subject, 'failed', $error, $by?->id);

            return false;
        }
    }

    private function log(?int $companyId, ?int $subscriptionId, ?int $paymentId, string $type, ?string $periodEnd, array $to, string $subject, string $status, ?string $error, ?int $userId): void
    {
        try {
            PlatformMailLog::create([
                'company_id' => $companyId, 'subscription_id' => $subscriptionId, 'payment_id' => $paymentId, 'type' => $type, 'period_end' => $periodEnd,
                'recipients' => implode(', ', $to), 'subject' => mb_substr($subject, 0, 250), 'status' => $status, 'error' => $error, 'triggered_by' => $userId, 'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
