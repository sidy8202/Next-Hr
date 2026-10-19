<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Candidate;
use App\Services\CandidateService;
use App\Support\CandidateProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Banque de CV')]
class CandidateIndex extends Component
{
    use AuthorizesResource, WithPagination;

    private const FILTERS = ['search', 'profile', 'seniority', 'education', 'skill', 'status'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'domaine')]
    public string $profile = '';

    #[Url(as: 'anciennete')]
    public string $seniority = '';

    #[Url(as: 'etudes')]
    public string $education = '';

    #[Url(as: 'competence')]
    public string $skill = '';

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'candidates');
    }

    public function updated($name): void
    {
        if (in_array($name, self::FILTERS, true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    #[Computed]
    public function candidates()
    {
        $term = trim($this->search);
        $seniorityRange = config('recruitment.seniority.'.$this->seniority);

        return Candidate::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('current_job_title', 'like', "%{$term}%")))
            ->when($this->profile !== '', fn ($q) => $q->where('profile_category', $this->profile))
            ->when($this->education !== '', fn ($q) => $q->whereIn('education_level', CandidateProfile::levelsAtLeast($this->education)))
            ->when($seniorityRange, function ($q) use ($seniorityRange) {
                $q->where('experience_years', '>=', $seniorityRange['min']);

                if ($seniorityRange['max'] !== null) {
                    $q->where('experience_years', '<', $seniorityRange['max']);
                }
            })
            ->when(trim($this->skill) !== '', fn ($q) => $q->whereJsonContains('skills', mb_strtolower(trim($this->skill))))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(15);
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('delete', 'candidates');

        app(CandidateService::class)->delete(Candidate::findOrFail($id)); // filtré par entreprise

        unset($this->candidates);
        $this->dispatch('notify', type: 'success', message: 'Profil supprimé.');
    }

    public function archive(int $id): void
    {
        $this->authorizeAction('update', 'candidates');

        $candidate = Candidate::findOrFail($id);
        app(CandidateService::class)->setStatus($candidate, $candidate->status === 'archived' ? 'active' : 'archived');

        unset($this->candidates);
    }

    public function render()
    {
        return view('livewire.recruitment.candidate-index');
    }
}
