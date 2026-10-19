<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccountCreated extends Mailable
{
    public function __construct(
        public string $name,
        public string $companyName,
        public string $loginEmail,
        public string $password,
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre accès à Next HR – {$this->companyName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-created');
    }
}
