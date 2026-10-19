<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\JobOffer;
use App\Models\Position;
use App\Services\JobOfferService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Offre d’emploi')]
class JobOfferForm extends Component
{
    use AuthorizesResource;

    public ?JobOffer $jobOffer = null;

    public string $title = '';
    public $department_id = '';
    public $position_id = '';
    public string $profile_category = '';
    public string $contract_type = 'CDI';
    public string $description = '';
    public string $requirements = '';
    public string $min_education_level = '';
    public $min_experience_years = 0;
    public $vacancies = 1;
    public string $closes_at = '';

    public function mount(?JobOffer $jobOffer = null): void
    {
        if ($jobOffer?->exists) {
            $this->authorizeAction('update', 'job_offers');
            $this->jobOffer = $jobOffer;

            foreach (['title', 'profile_category', 'contract_type', 'description', 'requirements', 'min_education_level'] as $f) {
                $this->{$f} = (string) ($jobOffer->{$f} ?? '');
            }

            $this->department_id = $jobOffer->department_id ?? '';
            $this->position_id = $jobOffer->position_id ?? '';
            $this->min_experience_years = $jobOffer->min_experience_years;
            $this->vacancies = $jobOffer->vacancies;
            $this->closes_at = $jobOffer->closes_at?->toDateString() ?? '';
        } else {
            $this->authorizeAction('create', 'job_offers');
            $this->jobOffer = null;
        }
    }

    public function updatedDepartmentId(): void
    {
        $this->position_id = ''; // le poste dépend du département
        unset($this->positions);
    }

    #[Computed]
    public function departments()
    {
        return Department::get()->sortBy(fn ($d) => $d->name ?? '')->values(); // filtré par entreprise
    }

    #[Computed]
    public function positions()
    {
        if (! $this->department_id) {
            return collect();
        }

        return Position::where('department_id', (int) $this->department_id)->get()
            ->sortBy(fn ($p) => $p->name ?? $p->title ?? '')->values();
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'title' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where('department_id', (int) $this->department_id)],
            'profile_category' => ['nullable', Rule::in(config('recruitment.profile_categories'))],
            'contract_type' => ['required', Rule::in(array_keys(config('recruitment.contract_types')))],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'min_education_level' => ['nullable', Rule::in(array_keys(config('recruitment.education_levels')))],
            'min_experience_years' => ['required', 'numeric', 'between:0,60'],
            'is_external' => ['required', 'integer'],
            'is_internal' => ['required', 'integer'],
            'vacancies' => ['required', 'integer', 'between:1,500'],
            'closes_at' => ['nullable', 'date'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'intitulé', 'department_id' => 'département', 'position_id' => 'poste',
            'profile_category' => 'domaine', 'contract_type' => 'type de contrat',
            'min_education_level' => 'niveau d’études minimum', 'min_experience_years' => 'expérience minimale',
            'vacancies' => 'nombre de postes', 'closes_at' => 'date de clôture',
        ];
    }

    public function save(): void
    {
        $this->authorizeAction($this->jobOffer ? 'update' : 'create', 'job_offers');
        $this->validate();

        $data = $this->only([
            'title', 'department_id', 'position_id', 'profile_category', 'contract_type', 'description',
            'requirements', 'min_education_level', 'min_experience_years', 'vacancies', 'is_internal','is_external', 'closes_at',
        ]);

        $service = app(JobOfferService::class);
        $offer = $this->jobOffer ? $service->update($this->jobOffer, $data) : $service->create($data);

        session()->flash('status', 'Offre enregistrée.');
        $this->redirectRoute('job-offers.show', ['jobOffer' => $offer->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.recruitment.job-offer-form');
    }
}
