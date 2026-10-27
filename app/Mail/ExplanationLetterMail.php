<?php

namespace App\Mail;

use App\Models\ExplanationCase;
use App\Models\ExplanationDocument;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ExplanationLetterMail extends Mailable
{
    public function __construct(public ExplanationCase $case, public ExplanationDocument $document, public string $company)
    {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->document->kind) {
            'response' => 'Réponse à la demande d’explication',
            'decision' => 'Décision — demande d’explication',
            default => 'Demande d’explication',
        };

        return new Envelope(subject: $subject.' — réf. '.$this->case->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.explanation-letter', with: [
            'case' => $this->case, 'kind' => $this->document->kind, 'company' => $this->company, 'employee' => $this->case->employee,
        ]);
    }

    public function attachments(): array
    {
        $ext = pathinfo($this->document->file_path, PATHINFO_EXTENSION);

        return [Attachment::fromStorageDisk('local', $this->document->file_path)->as('lettre-'.$this->document->reference.'.'.$ext)
            ->withMime($ext === 'pdf' ? 'application/pdf' : 'text/html')];
    }
}
