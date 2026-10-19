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

        return Application::where('job_offer_id', $this->jobOffer->id)
            ->with('candidate')
            ->get()
            ->sortBy(fn ($a) => array_search($a->stage, $order, true))
            ->values();
    }

    #[Computed]
    public function stageCounts(): array
    {
        $counts = $this->applications->countBy('stage');

        return collect(config('recruitment.stages'))
            ->map(fn ($s, $key) => ['label' => $s['label'], 'count' => $counts->get($key, 0)])
            ->all();
    }

    private function availableCandidates()
    {
        return Candidate::where('status', 'active')
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

    private function refreshData(): void
    {
        $this->jobOffer->refresh();
        unset($this->applications, $this->stageCounts, $this->suggestions, $this->searchResults);
    }

    public function render()
    {
        return view('livewire.recruitment.job-offer-show');
    }
}
