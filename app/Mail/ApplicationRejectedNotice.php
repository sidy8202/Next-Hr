<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ApplicationRejectedNotice extends Mailable
{
    public function __construct(public Application $application, public string $company)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre candidature — '.$this->application->jobOffer?->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.application-rejected', with: [
            'candidateName' => $this->application->candidate?->full_name,
            'jobTitle' => $this->application->jobOffer?->title,
            'company' => $this->company,
        ]);
    }
}
