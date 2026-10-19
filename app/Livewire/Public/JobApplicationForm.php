<?php

namespace App\Livewire\Public;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guest')]
class JobApplicationForm extends Component
{
    use WithFileUploads;

    public Company $company;
    public JobOffer $jobOffer;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $city = '';
    public string $degree = '';
    public int $experience_years = 0;
    public string $cover_letter = '';
    public $cv_file;

    // Consentements
    public bool $consent_process = false;
    public bool $consent_keep_cv = false;

    // Anti-Spam HoneyPot
    public string $website = '';

    public bool $submitted = false;

    public function mount(Company $company, JobOffer $jobOffer): void
    {
        abort_unless($jobOffer->company_id === $company->id, 404);
        abort_unless($jobOffer->status === 'published', 404);

        $this->company = $company;
        $this->jobOffer = $jobOffer;
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'degree' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'cv_file' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx'],
            'consent_process' => ['accepted'],
            'consent_keep_cv' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'adresse e-mail',
            'phone' => 'numéro de téléphone',
            'experience_years' => "années d'expérience",
            'cv_file' => 'CV (Curriculum Vitae)',
            'consent_process' => 'consentement de traitement de la candidature',
        ];
    }

    public function submit(): void
    {
        // Anti-spam honeypot
        if (! empty($this->website)) {
            return;
        }

        $this->validate();

        DB::transaction(function () {
            // 1. Sauvegarde sécurisée du CV sur le disque privé
            $path = $this->cv_file->store("candidates/{$this->company->id}/cvs", 'local');

            // 2. Recherche ou création du Candidat dans la banque de CV
            $candidate = Candidate::where('company_id', $this->company->id)
                ->where('email', trim(strtolower($this->email)))
                ->first();

            if (! $candidate) {
                $candidate = new Candidate();
                $candidate->company_id = $this->company->id;
            }

            $candidate->fill([
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
                'email' => trim(strtolower($this->email)),
                'phone' => trim($this->phone),
                'city' => trim($this->city),
                'degree' => trim($this->degree),
                'experience_years' => $this->experience_years,
                'cv_path' => $path,
                'source' => 'careers_page',
                'consent_keep_cv' => $this->consent_keep_cv,
                'consent_until' => $this->consent_keep_cv ? now()->addMonths(24) : now()->addMonths(6),
            ]);
            $candidate->save();

            // 3. Création de la Candidature rattachée à l'Offre au statut initial "new"
            JobApplication::create([
                'company_id' => $this->company->id,
                'job_offer_id' => $this->jobOffer->id,
                'candidate_id' => $candidate->id,
                'stage' => 'new',
                'cover_letter' => trim($this->cover_letter),
                'applied_at' => now(),
            ]);
        });

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public.job-application-form')
            ->title("Candidater à {$this->jobOffer->title} · {$this->company->name}");
    }
}