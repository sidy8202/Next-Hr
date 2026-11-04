<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\PlatformProfile;
use App\Models\SubscriptionPayment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionInvoiceMail extends Mailable
{
    public function __construct(
        public SubscriptionPayment $payment,
        public Company $company,
        public PlatformProfile $profile,
        public string $content,
        public string $ext,
        public string $mime,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre facture '.$this->payment->invoice_number.' : '.$this->profile->legal_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.platform-invoice-mail', with: ['payment' => $this->payment, 'company' => $this->company, 'profile' => $this->profile]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->content, 'facture-'.$this->payment->invoice_number.'.'.$this->ext)->withMime($this->mime)];
    }
}
