<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ApplicationReceivedNotice extends Mailable
{
    public function __construct(public string $candidateName, public string $jobTitle, public string $company)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Candidature reçue — '.$this->jobTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.application-received', with: [
            'candidateName' => $this->candidateName,
            'jobTitle' => $this->jobTitle,
            'company' => $this->company,
        ]);
    }
}
