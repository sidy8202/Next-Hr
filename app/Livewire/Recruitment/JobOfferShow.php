<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobOffer;
use App\Services\ApplicationService;
use App\Services\CandidateNotifier;
use App\Services\JobOfferService;
use App\Support\CandidateProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Offre d’emploi')]
class JobOfferShow extends Component
{
    use AuthorizesResource;

    public JobOffer $jobOffer;

    public string $candidateSearch = '';

    /** '' | match | review | low : filtre sur le score de correspondance */
    public string $matchFilter = '';

    /** @var array<int, string> étape choisie par candidature */
    public array $moveTo = [];

    /** @var array<int, string> */
    public array $notes = [];

    /** @var array<int, string> */
    public array $reasons = [];

    /** @var array<int, bool> informer le candidat par email lors d'un refus */
    public array $notify = [];

    public function mount(JobOffer $jobOffer): void
    {
        $this->authorizeAction('read', 'job_offers');
        $this->jobOffer = $jobOffer;
    }

    // ---------- Données ----------

    #[Computed]
    public function canSeeApplications(): bool
    {
        return auth()->user()->canDo('read', 'applications');
    }

    #[Computed]
    public function applications()
    {
        if (! $this->canSeeApplications) {
            return collect();
        }

        $order = array_keys(config('recruitment.stages'));
        $short = (int) config('careers.shortlist_threshold');
        $review = (int) config('careers.review_threshold');

        return Application::where('job_offer_id', $this->jobOffer->id)
            ->with('candidate')
            ->get()
            ->filter(fn ($a) => match ($this->matchFilter) {
                'match' => $a->match_score !== null && $a->match_score >= $short,
                'review' => $a->match_score !== null && $a->match_score >= $review && $a->match_score < $short,
                'low' => $a->match_score !== null && $a->match_score < $review,
                default => true,
            })
            ->sort(function ($a, $b) use ($order) {
                // d'abord par étape du pipeline, puis par score décroissant
                return [array_search($a->stage, $order, true), -($a->match_score ?? -1)]
                    <=> [array_search($b->stage, $order, true), -($b->match_score ?? -1)];
            })
            ->values();
    }

    #[Computed]
    public function stageCounts(): array
    {
        $counts = Application::where('job_offer_id', $this->jobOffer->id)->get(['stage'])->countBy('stage');

        return collect(config('recruitment.stages'))
            ->map(fn ($s, $key) => ['label' => $s['label'], 'count' => $counts->get($key, 0)])
            ->all();
    }

    /** Nombre de candidatures par niveau de correspondance (toutes étapes confondues). */
    #[Computed]
    public function bandCounts(): array
    {
        $scores = Application::where('job_offer_id', $this->jobOffer->id)->pluck('match_score');
        $short = (int) config('careers.shortlist_threshold');
        $review = (int) config('careers.review_threshold');
        $scored = $scores->filter(fn ($s) => $s !== null);

        return [
            'all' => $scores->count(),
            'match' => $scored->filter(fn ($s) => $s >= $short)->count(),
            'review' => $scored->filter(fn ($s) => $s >= $review && $s < $short)->count(),
            'low' => $scored->filter(fn ($s) => $s < $review)->count(),
            'scored' => $scored->count(),
        ];
    }

    private function availableCandidates()
    {
        return Candidate::where('status', 'active')->where('bank_consent', true)
            ->whereNotIn('id', Application::where('job_offer_id', $this->jobOffer->id)->select('candidate_id'));
    }

    /** Profils de la banque de CV qui répondent aux critères de l'offre (domaine, études, expérience). */
    #[Computed]
    public function suggestions()
    {
        $offer = $this->jobOffer;

        return $this->availableCandidates()
            ->when($offer->profile_category, fn ($q) => $q->where('profile_category', $offer->profile_category))
            ->when($offer->min_education_level, fn ($q) => $q->whereIn('education_level', CandidateProfile::levelsAtLeast($offer->min_education_level)))
            ->where('experience_years', '>=', $offer->min_experience_years)
            ->orderByDesc('experience_years')
            ->limit(8)->get();
    }

    #[Computed]
    public function searchResults()
    {
        $term = trim($this->candidateSearch);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        return $this->availableCandidates()
            ->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "{$term}%")
                ->orWhere('current_job_title', 'like', "%{$term}%"))
            ->orderBy('last_name')->limit(8)->get();
    }

    // ---------- Actions ----------

    public function addCandidate(int $candidateId): void
    {
        $this->authorizeAction('create', 'applications');

        $candidate = Candidate::findOrFail($candidateId); // filtré par entreprise
        app(ApplicationService::class)->apply($this->jobOffer, $candidate);

        $this->candidateSearch = '';
        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: 'Candidature ajoutée.');
    }

    public function move(int $applicationId): void
    {
        $this->authorizeAction('update', 'applications');

        $application = Application::where('job_offer_id', $this->jobOffer->id)->findOrFail($applicationId);

        $to = (string) ($this->moveTo[$applicationId] ?? '');
        $informCandidate = $to === 'rejected' && ! empty($this->notify[$applicationId]);

        $moved = app(ApplicationService::class)->move(
            $application,
            $to,
            $this->notes[$applicationId] ?? null,
            $this->reasons[$applicationId] ?? null,
        );

        $message = 'Candidature mise à jour.';

        if ($informCandidate) {
            // Après l'enregistrement : un échec d'envoi n'annule jamais le refus.
            $message .= match (app(CandidateNotifier::class)->rejected($moved)) {
                'sent' => ' Le candidat a été informé par email.',
                'failed' => ' L’envoi de l’email au candidat a échoué.',
                default => ' Ce candidat n’a pas d’adresse email.',
            };
        }

        unset($this->moveTo[$applicationId], $this->notes[$applicationId], $this->reasons[$applicationId], $this->notify[$applicationId]);
        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function rate(int $applicationId, int $rating): void
    {
        $this->authorizeAction('update', 'applications');

        $application = Application::where('job_offer_id', $this->jobOffer->id)->findOrFail($applicationId);
        app(ApplicationService::class)->rate($application, $rating);

        unset($this->applications);
    }

    public function setStatus(string $status): void
    {
        $this->authorizeAction('update', 'job_offers');

        app(JobOfferService::class)->setStatus($this->jobOffer, $status);

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: 'Statut de l’offre mis à jour.');
    }

    /** Passe en « Présélection » les candidatures « Nouvelle » dont le score atteint le seuil (action volontaire, jamais automatique). */
    public function shortlistMatching(): void
    {
        $this->authorizeAction('update', 'applications');

        $service = app(ApplicationService::class);
        $count = 0;

        Application::where('job_offer_id', $this->jobOffer->id)
            ->where('stage', 'new')
            ->whereNotNull('match_score')
            ->where('match_score', '>=', (int) config('careers.shortlist_threshold'))
            ->orderByDesc('match_score')
            ->get()
            ->each(function ($application) use ($service, &$count) {
                $service->move($application, 'screening', 'Présélection : profil correspondant à l’offre (score '.$application->match_score.'/100)');
                $count++;
            });

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: $count
            ? "{$count} candidature(s) présélectionnée(s)."
            : 'Aucune candidature « Nouvelle » n’atteint le seuil de correspondance.');
    }

    public function recomputeScores(): void
    {
        $this->authorizeAction('update', 'applications');

        $count = app(ApplicationService::class)->rescore($this->jobOffer);

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: "Score recalculé pour {$count} candidature(s).");
    }

    private function refreshData(): void
    {
        $this->jobOffer->refresh();
        unset($this->applications, $this->stageCounts, $this->bandCounts, $this->suggestions, $this->searchResults);
    }

    public function render()
    {
        return view('livewire.recruitment.job-offer-show');
    }
}
