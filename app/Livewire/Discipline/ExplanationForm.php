<?php

namespace App\Livewire\Discipline;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Employee;
use App\Models\Letterhead;
use App\Services\ExplanationService;
use App\Support\LeaveVisibility;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/** Rédaction d'une demande d'explication : champs de la lettre + aperçu en direct (même rendu que le PDF). */
#[Layout('components.layouts.app')]
#[Title('Nouvelle demande d’explication')]
class ExplanationForm extends Component
{
    use AuthorizesResource;

    public $employee_id = '';
    public string $template = '';
    public string $subject = '';
    public string $body = '';
    public $response_days = 3;
    public string $copies = 'Direction des Ressources Humaines';
    public string $signatory_name = '';
    public string $signatory_title = '';

    public function mount(): void
    {
        $this->authorizeAction('create', 'explanation_letters');

        $user = auth()->user();
        $me = $user->employee_id ? Employee::withoutGlobalScopes()->with('position:id,title')->find($user->employee_id) : null;

        $this->signatory_name = $me?->full_name ?? $user->name;
        $this->signatory_title = (string) ($me?->position?->title ?? '');
        $this->response_days = Letterhead::for($user->company_id)->default_response_days;
    }

    /** Employés auxquels cet utilisateur peut adresser une lettre (son équipe pour un chef, tous pour la RH), hors lui-même. */
    #[Computed]
    public function employees()
    {
        $ids = LeaveVisibility::employeeIds(auth()->user(), 'explanation_letters');

        return Employee::where('status', 'active')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->when(auth()->user()->employee_id, fn ($q, $id) => $q->where('id', '!=', $id))
            ->orderBy('last_name')->get(['id', 'company_id', 'matricule', 'first_name', 'last_name', 'department_id', 'position_id', 'work_schedule_id']);
    }

    #[Computed]
    public function target(): ?Employee
    {
        // Fiche COMPLÈTE (avec company_id, planning…) : la liste ci-dessus n'est qu'un extrait pour le menu déroulant.
        return ($this->employee_id && $this->employees->contains('id', (int) $this->employee_id))
            ? Employee::with(['department:id,name', 'position:id,title'])->find((int) $this->employee_id)
            : null;
    }

    #[Computed]
    public function letterhead(): Letterhead
    {
        return Letterhead::for(auth()->user()->company_id);
    }

    #[Computed]
    public function deadline()
    {
        return $this->target && (int) $this->response_days > 0
            ? app(ExplanationService::class)->deadlineFor($this->target, (int) $this->response_days)
            : null;
    }

    public function updatedTemplate(): void
    {
        $t = config("discipline.request_templates.{$this->template}");

        if ($t) {
            $this->subject = $t['subject'];
            $this->body = $t['body'];
        }
    }

    public function save(): void
    {
        $this->authorizeAction('create', 'explanation_letters');

        $this->validate([
            'employee_id' => ['required'],
            'subject' => ['required', 'string', 'min:5', 'max:190'],
            'body' => ['required', 'string', 'min:20', 'max:6000'],
            'response_days' => ['required', 'integer', 'between:1,30'],
            'copies' => ['nullable', 'string', 'max:255'],
            'signatory_name' => ['required', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
        ], [], ['employee_id' => 'employé', 'subject' => 'objet', 'body' => 'corps de la lettre', 'response_days' => 'délai de réponse', 'signatory_name' => 'signataire']);

        abort_unless($this->target, 422);

        try {
            $case = app(ExplanationService::class)->issue(auth()->user(), $this->target, $this->only([
                'subject', 'body', 'response_days', 'copies', 'signatory_name', 'signatory_title',
            ]));
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e); // détail dans storage/logs/laravel.log
            $this->addError('explanation', config('app.debug') ? 'Envoi impossible : '.$e->getMessage() : 'Envoi impossible : rien n’a été enregistré. Réessayez ou contactez l’administrateur.');

            return;
        }

        session()->flash('status', 'Demande d’explication envoyée à l’employé et à la RH, avec la lettre en PDF.');
        $this->redirectRoute('explanations.show', ['case' => $case->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.discipline.explanation-form');
    }
}
