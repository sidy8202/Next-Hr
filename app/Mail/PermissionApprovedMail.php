<?php

namespace App\Mail;

use App\Models\PermissionRequest;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PermissionApprovedMail extends Mailable
{
    public function __construct(
        public PermissionRequest $request,
        public string $company,
        public string $path,
        public string $extension,
        public ?Carbon $resume,
        public string $messageText,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre permission est accordée — '.$this->request->periodLabel());
    }

    public function content(): Content
    {
        return new Content(view: 'mail.permission-approved', with: [
            'name' => $this->request->employee->full_name, 'request' => $this->request, 'company' => $this->company,
            'resume' => $this->resume, 'messageText' => $this->messageText,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->path)->as('fiche-de-permission.'.$this->extension)
                ->withMime($this->extension === 'pdf' ? 'application/pdf' : 'text/html'),
        ];
    }
}
