<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\JobOffer;
use App\Services\JobOfferService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Offres d’emploi')]
class JobOfferIndex extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'job_offers');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function offers()
    {
        return JobOffer::query()
            ->with(['department', 'position'])
            ->withCount('applications')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(15);
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('delete', 'job_offers');

        app(JobOfferService::class)->delete(JobOffer::findOrFail($id)); // filtré par entreprise

        unset($this->offers);
        $this->dispatch('notify', type: 'success', message: 'Offre supprimée.');
    }

    public function render()
    {
        return view('livewire.recruitment.job-offer-index');
    }
}
