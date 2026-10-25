<?php

namespace App\Services;

use App\Mail\ApplicationReceivedNotice;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\Candidate;
use App\Models\JobOffer;
use App\Support\MatchScorer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Candidature déposée par une personne NON connectée (page publique de l'entreprise).
 * Pas d'utilisateur connecté ici : l'entreprise vient de l'OFFRE, jamais de la session,
 * et rien ne passe par les filtres automatiques (withoutGlobalScopes + company_id explicite, enregistrement sans événements).
 */
class PublicApplicationService
{
    /** L'offre est-elle visible et ouverte au public ? */
    public static function isOpen(JobOffer $offer): bool
    {
        return $offer->is_public
            && $offer->status === 'open'
            && (! $offer->closes_at || $offer->closes_at->gte(today()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{application: Application, candidate: Candidate}
     */
    public function submit(JobOffer $offer, array $data, UploadedFile $cv, ?UploadedFile $letter, bool $bankConsent): array
    {
        $companyId = $offer->company_id;
        $stored = [];
        $oldCv = null;

        try {
            $result = DB::transaction(function () use ($offer, $data, $cv, $letter, $bankConsent, $companyId, &$stored, &$oldCv) {
                $offer = JobOffer::withoutGlobalScopes()->whereKey($offer->id)->lockForUpdate()->firstOrFail();

                if (! self::isOpen($offer)) {
                    throw ValidationException::withMessages(['offer' => 'Cette offre n’accepte plus de candidatures.']);
                }

                $email = mb_strtolower(trim((string) $data['email']));
                $candidate = Candidate::withoutGlobalScopes()
                    ->where('company_id', $companyId)->where('email', $email)->lockForUpdate()->first();

                if ($candidate && Application::withoutGlobalScopes()
                    ->where('job_offer_id', $offer->id)->where('candidate_id', $candidate->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'Vous avez déjà postulé à cette offre avec cette adresse email.']);
                }

                [$cvPath, $cvName] = $this->store($companyId, 'candidates', $cv);
                $stored[] = $cvPath;

                $retention = $bankConsent
                    ? now()->addMonths((int) config('recruitment.retention_months'))
                    : now()->addMonths((int) config('careers.process_retention_months'));

                $skills = $this->skills($data['skills'] ?? '');

                if ($candidate) {
                    $oldCv = $candidate->cv_path;
                    $this->updateExisting($candidate, $data, $skills, $cvPath, $cvName, $bankConsent, $retention);
                } else {
                    $candidate = new Candidate();
                    $candidate->forceFill([
                        'company_id' => $companyId,
                        'first_name' => trim($data['first_name']),
                        'last_name' => trim($data['last_name']),
                        'email' => $email,
                        'phone' => $this->clean($data['phone'] ?? null),
                        'city' => $this->clean($data['city'] ?? null),
                        'education_level' => $data['education_level'] ?: 'none',
                        'last_degree' => $this->clean($data['last_degree'] ?? null),
                        'experience_years' => round(max(0, (float) ($data['experience_years'] ?? 0)), 1),
                        'current_job_title' => $this->clean($data['current_job_title'] ?? null),
                        'profile_category' => $this->clean($data['profile_category'] ?? null),
                        'skills' => $skills,
                        'source' => 'website',
                        'cv_path' => $cvPath,
                        'cv_original_name' => $cvName,
                        'status' => 'active',
                        'consent_given_at' => now(),
                        'retention_until' => $retention->toDateString(),
                        'bank_consent' => $bankConsent,
                    ])->saveQuietly();
                }

                $letterPath = null;

                if ($letter) {
                    [$letterPath] = $this->store($companyId, 'applications', $letter);
                    $stored[] = $letterPath;
                }

                $match = MatchScorer::score($offer, $candidate->refresh());

                $application = new Application();
                $application->forceFill([
                    'company_id' => $companyId,
                    'job_offer_id' => $offer->id,
                    'candidate_id' => $candidate->id,
                    'stage' => 'new',
                    'channel' => 'portal',
                    'match_score' => $match['score'],
                    'match_details' => $match['details'],
                    'cover_letter_path' => $letterPath,
                    'applied_at' => now(),
                    'stage_changed_at' => now(),
                ])->saveQuietly();

                ApplicationEvent::create([
                    'application_id' => $application->id, 'from_stage' => null, 'to_stage' => 'new',
                    'note' => 'Candidature reçue via la page publique', 'user_id' => null,
                ]);

                return ['application' => $application, 'candidate' => $candidate];
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->deleteFiles($stored);

            throw ValidationException::withMessages(['email' => 'Une candidature avec cette adresse est déjà en cours d’enregistrement. Réessayez dans un instant.']);
        } catch (Throwable $e) {
            $this->deleteFiles($stored); // pas de fichier orphelin si tout est annulé

            throw $e;
        }

        // L'ancien CV n'est supprimé qu'une fois la transaction validée.
        if ($oldCv && $oldCv !== $result['candidate']->cv_path) {
            $this->deleteFiles([$oldCv]);
        }

        $this->confirm($offer, $result['candidate']);

        return $result;
    }

    // ---------- Internes ----------

    /** Le profil existe déjà (même email) : on ne complète que ce qui est vide, et on met le CV à jour. */
    private function updateExisting(Candidate $c, array $data, array $skills, string $cvPath, string $cvName, bool $bankConsent, $retention): void
    {
        $c->forceFill([
            'cv_path' => $cvPath,
            'cv_original_name' => $cvName,
            'status' => $c->status === 'archived' ? 'active' : $c->status,
            'phone' => $c->phone ?: $this->clean($data['phone'] ?? null),
            'city' => $c->city ?: $this->clean($data['city'] ?? null),
            'last_degree' => $c->last_degree ?: $this->clean($data['last_degree'] ?? null),
            'current_job_title' => $c->current_job_title ?: $this->clean($data['current_job_title'] ?? null),
            'profile_category' => $c->profile_category ?: $this->clean($data['profile_category'] ?? null),
            'education_level' => ($c->education_level && $c->education_level !== 'none') ? $c->education_level : ($data['education_level'] ?: 'none'),
            'experience_years' => (float) $c->experience_years > 0 ? $c->experience_years : round(max(0, (float) ($data['experience_years'] ?? 0)), 1),
            'skills' => array_values(array_unique(array_merge($c->skills ?? [], $skills))),
            'bank_consent' => $c->bank_consent || $bankConsent,
            'consent_given_at' => now(),
            'retention_until' => max($c->retention_until?->toDateString() ?? '', $retention->toDateString()),
        ])->saveQuietly();
    }

    private function confirm(JobOffer $offer, Candidate $candidate): void
    {
        $company = DB::table('companies')->where('id', $offer->company_id)->value('name') ?? config('app.name');

        try {
            Mail::to($candidate->email)->send(new ApplicationReceivedNotice($candidate->full_name, $offer->title, (string) $company));
        } catch (Throwable $e) {
            report($e); // l'échec de l'email de confirmation n'annule jamais la candidature
        }
    }

    /** @return array{0: string, 1: string} */
    private function store(int $companyId, string $folder, UploadedFile $file): array
    {
        $path = $file->storeAs("companies/{$companyId}/{$folder}", Str::uuid().'.'.$file->extension(), 'local');

        return [$path, Str::limit($file->getClientOriginalName(), 150, '')];
    }

    private function deleteFiles(array $paths): void
    {
        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function skills(array|string|null $raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[,;\n]+/', (string) $raw);

        return collect($items)->map(fn ($s) => mb_strtolower(trim((string) $s)))->filter()->unique()->take(30)->values()->all();
    }
}
