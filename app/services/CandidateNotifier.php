<?php

namespace App\Services;

use App\Mail\ApplicationRejectedNotice;
use App\Mail\InterviewNotice;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails du recrutement. Toujours appelé APRÈS l'enregistrement : un échec d'envoi n'annule jamais l'action.
 * Statuts : sent | failed | none (pas d'adresse).
 */
class CandidateNotifier
{
    /** @return array{candidate: string, interviewer: string} */
    public function interview(Interview $interview, string $kind, bool $candidate = true): array
    {
        $interview->loadMissing(['application.candidate', 'application.jobOffer', 'interviewer']);
        $company = $this->company();

        $targets = [
            'candidate' => $candidate ? $interview->application->candidate?->email : null,
            'interviewer' => $interview->interviewer?->email,
        ];
        $status = ['candidate' => 'none', 'interviewer' => 'none'];

        foreach ($targets as $audience => $email) {
            if (! $email) {
                continue;
            }

            try {
                Mail::to($email)->send(new InterviewNotice($interview, $kind, $audience, $company));
                $status[$audience] = 'sent';
            } catch (Throwable $e) {
                report($e);
                $status[$audience] = 'failed';
            }
        }

        return $status;
    }

    /** Avis de refus au candidat : message courtois et générique, le motif interne n'est jamais transmis. */
    public function rejected(Application $application): string
    {
        $application->loadMissing(['candidate', 'jobOffer']);
        $email = $application->candidate?->email;

        if (! $email) {
            return 'none';
        }

        try {
            Mail::to($email)->send(new ApplicationRejectedNotice($application, $this->company()));

            return 'sent';
        } catch (Throwable $e) {
            report($e);

            return 'failed';
        }
    }

    private function company(): string
    {
        return auth()->user()->company?->name ?? config('app.name');
    }
}
