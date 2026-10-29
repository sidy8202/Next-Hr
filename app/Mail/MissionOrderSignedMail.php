<?php

namespace App\Mail;

use App\Models\Mission;
use App\Models\MissionDocument;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MissionOrderSignedMail extends Mailable
{
    public function __construct(
        public Mission $mission,
        public MissionDocument $document,
        public string $company,
        public string $participantName,
        public bool $forSelf,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ordre de mission signé — réf. '.$this->document->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.mission-order-signed', with: [
            'mission' => $this->mission, 'document' => $this->document, 'company' => $this->company,
            'participantName' => $this->participantName, 'forSelf' => $this->forSelf,
        ]);
    }

    public function attachments(): array
    {
        $ext = $this->document->extension();
        $mime = match ($ext) { 'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', default => 'application/octet-stream' };

        return [Attachment::fromStorageDisk('local', $this->document->file_path)->as('ordre-signe-'.$this->document->reference.'.'.$ext)->withMime($mime)];
    }
}
