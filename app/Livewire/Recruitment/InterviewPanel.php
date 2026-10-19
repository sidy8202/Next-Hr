<?php

namespace App\Livewire\Recruitment;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use App\Services\CandidateNotifier;
use App\Services\InterviewService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Entretiens d'UNE candidature, intégré à la page de l'offre. */
class InterviewPanel extends Component
{
    use AuthorizesResource;

    public int $applicationId;

    public bool $open = false;

    public ?int $editingId = null;

    public string $scheduled_at = '';
    public $duration_minutes = 60;
    public string $mode = 'onsite';
    public string $location = '';
    public $interviewer_id = '';
    public bool $notify = true;

    public function mount(int $applicationId): void
    {
        $this->authorizeAction('read', 'interviews');
        $this->applicationId = $applicationId;
    }

    #[Computed]
    public function application(): Application
    {
        return Application::with(['candidate', 'jobOffer'])->findOrFail($this->applicationId); // filtré par entreprise
    }

    #[Computed]
    public function interviews()
    {
        return Interview::with('interviewer')
            ->where('application_id', $this->applicationId)
            ->orderByDesc('scheduled_at')->get();
    }

    #[Computed]
    public function users()
    {
        return User::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    // ---------- Formulaire ----------

    public function openForm(): void
    {
        $this->authorizeAction('create', 'interviews');
        $this->resetForm();
        $this->open = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeAction('update', 'interviews');

        $interview = Interview::where('application_id', $this->applicationId)->findOrFail($id);

        $this->editingId = $interview->id;
        $this->scheduled_at = $interview->scheduled_at->format('Y-m-d\TH:i');
        $this->duration_minutes = $interview->duration_minutes;
        $this->mode = $interview->mode;
        $this->location = (string) $interview->location;
        $this->interviewer_id = $interview->interviewer_id ?? '';
        $this->notify = true;
        $this->open = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->open = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'scheduled_at', 'location', 'interviewer_id']);
        $this->duration_minutes = 60;
        $this->mode = 'onsite';
        $this->notify = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'between:15,480'],
            'mode' => ['required', Rule::in(array_keys(Interview::MODES))],
            'location' => [Rule::requiredIf($this->mode === 'video'), 'nullable', 'string', 'max:190'],
            'interviewer_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', auth()->user()->company_id)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'scheduled_at' => 'date et heure', 'duration_minutes' => 'durée', 'mode' => 'modalité',
            'location' => $this->mode === 'video' ? 'lien de visioconférence' : 'lieu',
            'interviewer_id' => 'évaluateur',
        ];
    }

    public function save(): void
    {
        $this->authorizeAction($this->editingId ? 'update' : 'create', 'interviews');
        $this->validate();

        $data = $this->only(['scheduled_at', 'duration_minutes', 'mode', 'location', 'interviewer_id']);
        $service = app(InterviewService::class);

        if ($this->editingId) {
            $interview = $service->reschedule(Interview::where('application_id', $this->applicationId)->findOrFail($this->editingId), $data);
            $kind = 'rescheduled';
        } else {
            $interview = $service->schedule($this->application, $data);
            $kind = 'scheduled';
        }

        $message = $kind === 'scheduled' ? 'Entretien planifié.' : 'Entretien déplacé.';

        if ($this->notify) {
            $status = app(CandidateNotifier::class)->interview($interview, $kind);
            $message .= $this->mailSummary($status);
        }

        $this->closeForm();
        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function cancelInterview(int $id): void
    {
        $this->authorizeAction('update', 'interviews');

        $interview = Interview::where('application_id', $this->applicationId)->findOrFail($id);
        $interview = app(InterviewService::class)->cancel($interview);

        $status = app(CandidateNotifier::class)->interview($interview, 'cancelled');

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: 'Entretien annulé.'.$this->mailSummary($status));
    }

    private function mailSummary(array $status): string
    {
        $parts = [];

        if ($status['candidate'] === 'sent') {
            $parts[] = 'Email envoyé au candidat.';
        } elseif ($status['candidate'] === 'failed') {
            $parts[] = 'L’envoi de l’email au candidat a échoué.';
        }
        if ($status['interviewer'] === 'sent') {
            $parts[] = 'Évaluateur prévenu.';
        } elseif ($status['interviewer'] === 'failed') {
            $parts[] = 'L’envoi de l’email à l’évaluateur a échoué.';
        }

        return $parts ? ' '.implode(' ', $parts) : '';
    }

    private function refreshData(): void
    {
        unset($this->interviews, $this->application);
        $this->dispatch('interviews-changed');
    }

    public function render()
    {
        return view('livewire.recruitment.interview-panel');
    }
}
