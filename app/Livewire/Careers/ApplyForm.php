<?php

namespace App\Livewire\Careers;

use App\Models\JobOffer;
use App\Services\PublicApplicationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Formulaire de candidature PUBLIC (aucune connexion requise). */
class ApplyForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $offerId;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $city = '';
    public string $education_level = '';
    public string $last_degree = '';
    public $experience_years = 0;
    public string $current_job_title = '';
    public string $profile_category = '';
    public string $skills = '';

    public $cv = null;
    public $letter = null;

    public bool $consent_process = false;
    public bool $consent_bank = false;

    /** Champ piège : invisible pour une personne, rempli par les robots. */
    public string $website = '';

    public bool $done = false;

    public function mount(int $offerId): void
    {
        $this->offerId = $offerId;
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:100'],
            'education_level' => ['required', Rule::in(array_keys(config('recruitment.education_levels')))],
            'last_degree' => ['nullable', 'string', 'max:150'],
            'experience_years' => ['required', 'numeric', 'between:0,60'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'profile_category' => ['required', Rule::in(config('recruitment.profile_categories'))],
            'skills' => ['nullable', 'string', 'max:500'],
            'cv' => ['required', 'file', 'mimes:'.implode(',', config('recruitment.cv.mimes')), 'max:'.config('recruitment.cv.max_kb')],
            'letter' => ['nullable', 'file', 'mimes:'.implode(',', config('recruitment.cv.mimes')), 'max:'.config('recruitment.cv.max_kb')],
            'consent_process' => ['accepted'],
            'consent_bank' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'prénom', 'last_name' => 'nom', 'email' => 'email', 'phone' => 'téléphone',
            'education_level' => 'niveau d’études', 'experience_years' => 'années d’expérience',
            'profile_category' => 'domaine', 'cv' => 'CV', 'letter' => 'lettre de motivation',
        ];
    }

    protected function messages(): array
    {
        return [
            'consent_process.accepted' => 'Vous devez accepter le traitement de votre candidature pour postuler.',
        ];
    }

    public function submit(): void
    {
        if ($this->website !== '') {
            $this->done = true; // robot : on feint le succès, rien n'est enregistré

            return;
        }

        $key = 'apply:'.request()->ip().':'.$this->offerId;

        if (RateLimiter::tooManyAttempts($key, (int) config('careers.apply_attempts_per_hour'))) {
            $this->addError('offer', 'Trop de candidatures envoyées depuis cette connexion. Réessayez plus tard.');

            return;
        }

        $this->validate();
        RateLimiter::hit($key, 3600);

        $offer = JobOffer::withoutGlobalScopes()->findOrFail($this->offerId);

        app(PublicApplicationService::class)->submit(
            $offer,
            $this->only([
                'first_name', 'last_name', 'email', 'phone', 'city', 'education_level', 'last_degree',
                'experience_years', 'current_job_title', 'profile_category', 'skills',
            ]),
            $this->cv,
            $this->letter,
            $this->consent_bank,
        );

        $this->done = true;
    }

    public function render()
    {
        return view('livewire.careers.apply-form');
    }
}
