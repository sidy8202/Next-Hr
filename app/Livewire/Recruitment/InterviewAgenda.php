<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Interview;
use App\Services\InterviewService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Entretiens')]
class InterviewAgenda extends Component
{
    use AuthorizesResource, WithPagination;

    #[Url(as: 'periode')]
    public string $period = 'upcoming'; // upcoming | past | all

    #[Url(as: 'mes')]
    public bool $mine = false;

    #[Url(as: 'statut')]
    public string $status = '';

    public ?int $completing = null;
    public string $outcome = 'done';
    public $score = 3;
    public string $feedback = '';

    public function mount(): void
    {
        $this->authorizeAction('read', 'interviews');
    }

    public function updated($name): void
    {
        if (in_array($name, ['period', 'mine', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function interviews()
    {
        return Interview::with(['application.candidate', 'application.jobOffer', 'interviewer'])
            ->when($this->period === 'upcoming', fn ($q) => $q->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at'))
            ->when($this->period === 'past', fn ($q) => $q->where('scheduled_at', '<', now()->startOfDay())->orderByDesc('scheduled_at'))
            ->when($this->period === 'all', fn ($q) => $q->orderByDesc('scheduled_at'))
            ->when($this->mine, fn ($q) => $q->where('interviewer_id', auth()->id()))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->paginate(15);
    }

    public function startComplete(int $id): void
    {
        $this->completing = $id;
        $this->outcome = 'done';
        $this->score = 3;
        $this->feedback = '';
        $this->resetValidation();
    }

    public function stopComplete(): void
    {
        $this->completing = null;
    }

    public function complete(): void
    {
        abort_unless($this->completing, 422);

        $interview = Interview::findOrFail($this->completing); // filtré par entreprise

        app(InterviewService::class)->complete(
            $interview,
            $this->outcome,
            $this->outcome === 'done' ? (int) $this->score : null,
            $this->feedback,
        );

        $this->completing = null;
        unset($this->interviews);
        $this->dispatch('notify', type: 'success', message: 'Entretien clôturé.');
    }

    public function render()
    {
        return view('livewire.recruitment.interview-agenda');
    }
}
