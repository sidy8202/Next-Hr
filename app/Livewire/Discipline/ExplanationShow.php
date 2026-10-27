<?php

namespace App\Livewire\Discipline;

use App\Models\ExplanationCase;
use App\Services\ExplanationService;
use App\Support\LeaveVisibility;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Demande d’explication')]
class ExplanationShow extends Component
{
    use WithFileUploads;

    public ExplanationCase $case;

    public string $response_body = '';
    public $response_attachment = null;

    public string $decision_type = '';
    public string $decision_subject = '';
    public string $decision_body = '';
    public string $suspension_start = '';
    public string $suspension_end = '';
    public bool $suspension_paid = false;
    public string $dismissal_effective_date = '';

    public string $withdraw_reason = '';

    public function mount(ExplanationCase $case): void
    {
        abort_unless(app(ExplanationService::class)->canView(auth()->user(), $case), 403);
        $this->case = $case;
        app(ExplanationService::class)->markViewed($case, auth()->user());
        $this->reloadCase();
    }

    private function reloadCase(): void
    {
        $this->case = ExplanationCase::with(['employee', 'issuer', 'decider', 'documents'])->findOrFail($this->case->id);
    }

    public function updatedDecisionType(): void
    {
        $t = config("discipline.decisions.{$this->decision_type}");

        if ($t) {
            $this->decision_subject = $t['subject'];
            $this->decision_body = str_replace('[date]', optional($this->case->responded_at)->format('d/m/Y') ?? '[date]', $t['body']);
        }
    }

    public function respond(): void
    {
        $this->validate([
            'response_body' => ['required', 'string', 'min:10', 'max:6000'],
            'response_attachment' => ['nullable', 'file', 'mimes:'.implode(',', config('leaves.attachment.mimes')), 'max:'.config('leaves.attachment.max_kb')],
        ], [], ['response_body' => 'réponse', 'response_attachment' => 'pièce jointe']);

        if (! $this->guarded(fn () => app(ExplanationService::class)->respond($this->case, auth()->user(), $this->response_body, $this->response_attachment))) {
            return;
        }

        $this->reset(['response_body', 'response_attachment']);
        $this->reloadCase();
        $this->dispatch('notify', type: 'success', message: 'Réponse envoyée : votre chef et la RH en sont informés.');
    }

    public function decide(): void
    {
        $ok = $this->guarded(fn () => app(ExplanationService::class)->decide($this->case, auth()->user(), [
            'type' => $this->decision_type, 'subject' => $this->decision_subject, 'body' => $this->decision_body,
            'suspension_start' => $this->suspension_start, 'suspension_end' => $this->suspension_end, 'suspension_paid' => $this->suspension_paid,
            'dismissal_effective_date' => $this->dismissal_effective_date,
        ]));

        if (! $ok) {
            return;
        }

        $this->reloadCase();
        $this->dispatch('notify', type: 'success', message: 'Décision enregistrée : la lettre est envoyée à l’employé, au chef et à la RH.');
    }

    /** Exécute une action ; une erreur inattendue s'affiche (détail dans laravel.log) au lieu de ne rien faire. */
    private function guarded(callable $action): bool
    {
        try {
            $action();

            return true;
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('explanation', config('app.debug') ? 'Action impossible : '.$e->getMessage() : 'Action impossible : rien n’a été enregistré.');

            return false;
        }
    }

    public function withdraw(): void
    {
        app(ExplanationService::class)->withdraw($this->case, auth()->user(), $this->withdraw_reason);

        $this->withdraw_reason = '';
        $this->reloadCase();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.discipline.explanation-show', [
            'isEmployee' => $user->employee_id && (int) $user->employee_id === $this->case->employee_id,
            'isHr' => LeaveVisibility::isHr($user, 'explanation_letters'),
            'isIssuer' => (int) $this->case->issued_by_user_id === $user->id,
        ]);
    }
}
