<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** kind : scheduled | rescheduled | cancelled — audience : candidate | interviewer */
class InterviewNotice extends Mailable
{
    public function __construct(
        public Interview $interview,
        public string $kind,
        public string $audience,
        public string $company,
    ) {
    }

    private function title(): string
    {
        return (string) $this->interview->application->jobOffer?->title;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->kind) {
            'rescheduled' => 'Entretien déplacé',
            'cancelled' => 'Entretien annulé',
            default => $this->audience === 'candidate' ? 'Invitation à un entretien' : 'Entretien planifié',
        };

        return new Envelope(subject: $subject.' — '.$this->title());
    }

    public function content(): Content
    {
        return new Content(view: 'mail.interview-notice', with: [
            'interview' => $this->interview,
            'kind' => $this->kind,
            'audience' => $this->audience,
            'company' => $this->company,
            'candidateName' => $this->interview->application->candidate?->full_name,
            'jobTitle' => $this->title(),
            'timezone' => config('app.timezone'),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->ics(), 'entretien.ics')->withMime('text/calendar'),
        ];
    }

    private function ics(): string
    {
        $i = $this->interview;
        $cancelled = $this->kind === 'cancelled';
        $esc = fn ($s) => str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], (string) $s);
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'next-hr.local';

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Next HR//Recrutement//FR',
            'METHOD:'.($cancelled ? 'CANCEL' : 'PUBLISH'),
            'BEGIN:VEVENT',
            "UID:interview-{$i->id}@{$host}",
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($i->scheduled_at),
            'DTEND:'.$fmt($i->endsAt()),
            'SUMMARY:'.$esc('Entretien — '.$this->title()),
            'LOCATION:'.$esc($i->location),
            'STATUS:'.($cancelled ? 'CANCELLED' : 'CONFIRMED'),
            'SEQUENCE:'.($cancelled ? 2 : ($this->kind === 'rescheduled' ? 1 : 0)),
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";
    }
}
