<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\PlatformProfile;
use App\Models\Subscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rappels automatiques : échéance proche, fin d'essai, paiement en retard, abonnement expiré. */
class SubscriptionNoticeMail extends Mailable
{
    public function __construct(public Subscription $sub, public Company $company, public PlatformProfile $profile, public string $type)
    {
    }

    public static function subjectFor(string $type): string
    {
        return match (true) {
            $type === 'expired' => 'Votre abonnement Next HR a expiré',
            $type === 'past_due' => 'Paiement de votre abonnement Next HR en retard',
            str_starts_with($type, 'trial_') => 'Votre essai Next HR se termine bientôt',
            $type === 'reminder_1' => 'Votre abonnement Next HR expire demain',
            default => 'Votre abonnement Next HR arrive à échéance',
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::subjectFor($this->type));
    }

    public function content(): Content
    {
        $end = str_starts_with($this->type, 'trial_') ? $this->sub->trial_ends_at : $this->sub->ends_at;

        return new Content(view: 'mail.platform-notice-mail', with: [
            'sub' => $this->sub, 'plan' => $this->sub->plan, 'company' => $this->company, 'profile' => $this->profile, 'type' => $this->type,
            'end' => $end, 'days' => $end ? (int) today()->diffInDays($end, false) : null,
            'price' => number_format((int) $this->sub->price, 0, ',', ' ').' '.(in_array($this->sub->currency, ['XOF', 'XAF'], true) ? 'FCFA' : $this->sub->currency),
        ]);
    }
}
