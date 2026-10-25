<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Support\LeaveCalendar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeaveApprovedMail extends Mailable
{
    public function __construct(public LeaveRequest $leave, public string $company, public string $path, public string $extension)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre congé est validé — '.$this->leave->periodLabel());
    }

    public function content(): Content
    {
        $employee = $this->leave->employee;

        return new Content(view: 'mail.leave-approved', with: [
            'name' => $employee->full_name,
            'leave' => $this->leave,
            'company' => $this->company,
            'resume' => LeaveCalendar::nextWorkday($employee->company_id, $this->leave->end_date, $employee),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->path)
                ->as('fiche-de-conge.'.$this->extension)
                ->withMime($this->extension === 'pdf' ? 'application/pdf' : 'text/html'),
        ];
    }
}
