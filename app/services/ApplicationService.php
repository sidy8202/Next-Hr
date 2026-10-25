<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\Candidate;
use App\Models\JobOffer;
use App\Support\MatchScorer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    /** Enregistre la candidature d'un profil de la banque de CV à une offre ouverte. */
    public function apply(JobOffer $offer, Candidate $candidate): Application
    {
        abort_unless($offer->company_id === $candidate->company_id, 404);

        if ($offer->status !== 'open') {
            throw ValidationException::withMessages(['candidate' => 'Cette offre n’est pas ouverte aux candidatures.']);
        }
        if ($candidate->status !== 'active') {
            throw ValidationException::withMessages(['candidate' => 'Ce profil n’est pas actif : réactivez-le avant de le proposer.']);
        }

        if (! $candidate->bank_consent) {
            throw ValidationException::withMessages([
                'candidate' => 'Ce candidat n’a pas accepté que son CV soit conservé pour d’autres offres.',
            ]);
        }

        return DB::transaction(function () use ($offer, $candidate) {
            if (Application::where('job_offer_id', $offer->id)->where('candidate_id', $candidate->id)->exists()) {
                throw ValidationException::withMessages(['candidate' => 'Ce profil a déjà postulé à cette offre.']);
            }

            $match = MatchScorer::score($offer, $candidate);

            $application = Application::create([
                'job_offer_id' => $offer->id,
                'candidate_id' => $candidate->id,
                'stage' => 'new',
                'channel' => 'internal',
                'match_score' => $match['score'],
                'match_details' => $match['details'],
                'applied_at' => now(),
                'stage_changed_at' => now(),
                'created_by' => auth()->id(),
            ]);

            ApplicationEvent::create([
                'application_id' => $application->id, 'from_stage' => null, 'to_stage' => 'new',
                'note' => 'Candidature enregistrée', 'user_id' => auth()->id(),
            ]);

            AuditLogger::log('created', 'applications', $application->id, null, [
                'job_offer_id' => $offer->id, 'candidate_id' => $candidate->id,
            ]);

            return $application;
        });
    }

    /** Fait avancer (ou refuse) une candidature ; l'historique et l'offre suivent dans la même transaction. */
    public function move(Application $application, string $to, ?string $note = null, ?string $reason = null): Application
    {
        return DB::transaction(function () use ($application, $to, $note, $reason) {
            $app = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $from = $app->stage;

            if (! in_array($to, config("recruitment.stages.{$from}.next", []), true)) {
                throw ValidationException::withMessages([
                    'move' => 'Passage de « '.config("recruitment.stages.{$from}.label", $from).' » à « '
                        .config("recruitment.stages.{$to}.label", $to).' » impossible.',
                ]);
            }

            $reason = trim((string) $reason);

            if ($to === 'rejected' && mb_strlen($reason) < 3) {
                throw ValidationException::withMessages(['move' => 'Indiquez le motif du refus.']);
            }

            $offer = JobOffer::whereKey($app->job_offer_id)->lockForUpdate()->firstOrFail();
            $hired = 0;

            if ($to === 'hired') {
                $hired = Application::where('job_offer_id', $offer->id)->where('stage', 'hired')->count();

                if ($hired >= $offer->vacancies) {
                    throw ValidationException::withMessages(['move' => 'Tous les postes de cette offre sont déjà pourvus.']);
                }
            }

            $app->update([
                'stage' => $to,
                'stage_changed_at' => now(),
                'rejection_reason' => $to === 'rejected' ? $reason : null,
            ]);

            ApplicationEvent::create([
                'application_id' => $app->id, 'from_stage' => $from, 'to_stage' => $to,
                'note' => $to === 'rejected' ? trim($reason.' '.($note ? '— '.$note : '')) : (trim((string) $note) ?: null),
                'user_id' => auth()->id(),
            ]);

            if ($to === 'hired') {
                Candidate::whereKey($app->candidate_id)->update(['status' => 'hired']);

                if ($hired + 1 >= $offer->vacancies) {
                    $offer->update(['status' => 'filled']);
                }
            }

            AuditLogger::log('stage_changed', 'applications', $app->id, ['stage' => $from], ['stage' => $to]);

            return $app->fresh();
        });
    }

    /** Recalcule le score de correspondance de toutes les candidatures d'une offre (après un changement de critères). */
    public function rescore(JobOffer $offer): int
    {
        $count = 0;

        Application::where('job_offer_id', $offer->id)->with('candidate')->get()->each(function ($application) use ($offer, &$count) {
            if (! $application->candidate) {
                return;
            }

            $match = MatchScorer::score($offer, $application->candidate);
            $application->update(['match_score' => $match['score'], 'match_details' => $match['details']]);
            $count++;
        });

        return $count;
    }

    public function rate(Application $application, int $rating): Application
    {
        $application->update(['rating' => max(1, min(5, $rating))]);

        return $application;
    }
}
