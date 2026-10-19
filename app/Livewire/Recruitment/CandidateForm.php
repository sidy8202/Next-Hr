<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Candidate;
use App\Services\CandidateService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Profil candidat')]
class CandidateForm extends Component
{
    use AuthorizesResource, WithFileUploads;

    private const TEXT_FIELDS = [
        'first_name', 'last_name', 'email', 'phone', 'city', 'education_level', 'last_degree',
        'degree_field', 'school', 'current_job_title', 'profile_category', 'source', 'notes',
    ];

    public ?Candidate $candidate = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $city = '';
    public string $education_level = 'bac3';
    public string $last_degree = '';
    public string $degree_field = '';
    public string $school = '';
    public $graduation_year = null;
    public $experience_years = 0;
    public string $current_job_title = '';
    public string $profile_category = '';
    public string $skills = '';
    public string $source = '';
    public string $notes = '';

    public $cv = null;
    public bool $remove_cv = false;
    public bool $consent = false;

    public function mount(?Candidate $candidate = null): void
    {
        if ($candidate?->exists) {
            $this->authorizeAction('update', 'candidates');
            $this->candidate = $candidate;

            foreach (self::TEXT_FIELDS as $field) {
                $this->{$field} = (string) ($candidate->{$field} ?? '');
            }

            $this->graduation_year = $candidate->graduation_year;
            $this->experience_years = $candidate->experience_years;
            $this->skills = implode(', ', $candidate->skills ?? []);
            $this->consent = (bool) $candidate->consent_given_at;
        } else {
            $this->authorizeAction('create', 'candidates');
            $this->candidate = null;
        }
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190',
                Rule::unique('candidates', 'email')->where('company_id', $companyId)->ignore($this->candidate?->id)],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:100'],
            'education_level' => ['required', Rule::in(array_keys(config('recruitment.education_levels')))],
            'last_degree' => ['nullable', 'string', 'max:150'],
            'degree_field' => ['nullable', 'string', 'max:150'],
            'school' => ['nullable', 'string', 'max:150'],
            'graduation_year' => ['nullable', 'integer', 'between:1950,'.(date('Y') + 1)],
            'experience_years' => ['required', 'numeric', 'between:0,60'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'profile_category' => ['required', Rule::in(config('recruitment.profile_categories'))],
            'skills' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', Rule::in(array_keys(config('recruitment.sources')))],
            'notes' => ['nullable', 'string', 'max:3000'],
            'cv' => ['nullable', 'file', 'mimes:'.implode(',', config('recruitment.cv.mimes')), 'max:'.config('recruitment.cv.max_kb')],
            'consent' => $this->candidate ? ['boolean'] : ['accepted'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'prénom', 'last_name' => 'nom', 'email' => 'email', 'phone' => 'téléphone',
            'education_level' => 'niveau d’études', 'experience_years' => 'années d’expérience',
            'profile_category' => 'domaine', 'graduation_year' => 'année d’obtention', 'cv' => 'CV',
            'consent' => 'consentement',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required_without' => 'Renseignez au moins un email ou un téléphone.',
            'phone.required_without' => 'Renseignez au moins un email ou un téléphone.',
            'email.unique' => 'Un profil avec cet email existe déjà dans la banque de CV.',
            'consent.accepted' => 'Le consentement du candidat à la conservation de son CV est obligatoire.',
        ];
    }

    public function save(): void
    {
        $this->authorizeAction($this->candidate ? 'update' : 'create', 'candidates');
        $this->validate();

        $data = $this->only([...self::TEXT_FIELDS, 'graduation_year', 'experience_years', 'skills']);
        $service = app(CandidateService::class);

        if ($this->candidate) {
            $service->update($this->candidate, $data, $this->cv, $this->remove_cv);
        } else {
            $service->create($data, $this->cv);
        }

        session()->flash('status', 'Profil enregistré.');
        $this->redirectRoute('candidates.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.recruitment.candidate-form');
    }
}
