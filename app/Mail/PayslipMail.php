<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-mail « Votre bulletin de paie » : le bulletin en pièce jointe, sans aucun montant dans le corps du message. */
class PayslipMail extends Mailable
{
    public function __construct(
        public string $employeeName,
        public string $company,
        public string $periodLabel,
        public string $fileContent,
        public string $extension,
        public string $mime,
        public string $filename,
        public string $trackUrl,
        public string $appUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre bulletin de paie : ' . $this->periodLabel);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payslip-available', with: [
            'name' => $this->employeeName,
            'company' => $this->company,
            'periodLabel' => $this->periodLabel,
            'trackUrl' => $this->trackUrl,
            'appUrl' => $this->appUrl,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->fileContent, $this->filename)->withMime($this->mime),
        ];
    }
}
