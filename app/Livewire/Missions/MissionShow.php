<?php

namespace App\Livewire\Missions;

use App\Models\Mission;
use App\Services\MissionDocumentService;
use App\Services\MissionService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Ordre de mission')]
class MissionShow extends Component
{
    use WithFileUploads;

    public Mission $mission;

    /** Scans signés en cours de dépôt, par participant. */
    public array $uploads = [];

    /** Saisie RH par participant : perdiem_mode, days, daily_rate, salary_divisor, payment_mode */
    public array $review = [];

    public string $comment = '';
    public string $cancel_reason = '';

    public function mount(Mission $mission): void
    {
        abort_unless(app(MissionService::class)->canView(auth()->user(), $mission), 403);
        $this->mission = $mission;
        $this->reloadMission();
    }

    private function reloadMission(): void
    {
        $this->mission = Mission::with(['department', 'requester', 'participants.employee.position', 'participants.department', 'approvals.user', 'documents'])
            ->findOrFail($this->mission->id);

        $this->review = [];

        foreach ($this->mission->participants as $p) {
            $this->review[$p->id] = [
                'perdiem_mode' => $p->perdiem_mode->value === 'daily_salary' ? 'daily_salary' : 'manual',
                'days' => $p->days,
                'daily_rate' => $p->daily_rate ? (string) $p->daily_rate : '',
                'salary_divisor' => $p->salary_divisor ?: config('missions.salary_divisor'),
                'payment_mode' => $p->payment_mode,
            ];
        }
    }

    public function hrDecide(string $decision): void
    {
        $this->run(fn () => app(MissionService::class)->hrDecide($this->mission, auth()->user(), $decision, $this->comment, $this->review),
            match ($decision) { 'approved' => 'Mission validée : transmise au DG.', 'returned' => 'Mission renvoyée au demandeur.', default => 'Mission rejetée.' });
    }

    public function dgDecide(string $decision): void
    {
        $this->run(fn () => app(MissionService::class)->dgDecide($this->mission, auth()->user(), $decision, $this->comment),
            match ($decision) { 'approved' => 'Mission approuvée.', 'returned' => 'Mission renvoyée pour correction.', default => 'Mission rejetée.' });
    }

    public function cancel(): void
    {
        $this->run(fn () => app(MissionService::class)->cancel($this->mission, auth()->user(), $this->cancel_reason), 'Mission annulée.');
    }

    public function uploadSigned(int $participantId): void
    {
        $this->validate(
            ["uploads.{$participantId}" => ['required', 'file', 'mimes:'.config('missions.signed.mimes'), 'max:'.config('missions.signed.max_kb')]],
            [], ["uploads.{$participantId}" => 'document signé'],
        );

        $participant = $this->mission->participants->firstWhere('id', $participantId);
        abort_unless($participant, 404);
        $file = $this->uploads[$participantId];

        $this->run(fn () => app(MissionDocumentService::class)->uploadSigned($this->mission, $participant, $file, auth()->user()), 'Ordre signé enregistré : le participant, le demandeur et le chef du participant sont notifiés.');
        unset($this->uploads[$participantId]);
    }

    public function regenerateOrders(): void
    {
        $this->run(fn () => app(MissionDocumentService::class)->regenerate($this->mission, auth()->user()), 'Ordres générés.');
    }

    /** Pour chaque participant : ordre à signer et ordre signé accessibles à l'utilisateur. */
    private function docsFor($user): array
    {
        $svc = app(MissionDocumentService::class);
        $current = $this->mission->documents->whereNull('superseded_at');

        return $this->mission->participants->mapWithKeys(function ($p) use ($svc, $current, $user) {
            $order = $current->where('participant_id', $p->id)->firstWhere('kind', 'order');
            $signed = $current->where('participant_id', $p->id)->firstWhere('kind', 'signed');

            return [$p->id => [
                'order' => $order && $svc->canAccess($user, $this->mission, $order) ? $order : null,
                'hasOrder' => (bool) $order,
                'signed' => $signed && $svc->canAccess($user, $this->mission, $signed) ? $signed : null,
                'hasSigned' => (bool) $signed,
            ]];
        })->all();
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action();
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('mission', config('app.debug') ? 'Action impossible : '.$e->getMessage() : 'Action impossible : rien n’a été enregistré.');

            return;
        }

        $this->reset(['comment', 'cancel_reason']);
        $this->reloadMission();
        $this->dispatch('notify', type: 'success', message: $success);
    }

    public function render()
    {
        $svc = app(MissionService::class);
        $user = auth()->user();

        return view('livewire.missions.mission-show', [
            'isHr' => $svc->isHr($user),
            'isDg' => $svc->isDg($user),
            'canEdit' => $svc->canEdit($user, $this->mission),
            'canCancel' => $svc->canCancel($user, $this->mission),
            'docs' => $this->docsFor($user),
            'signedCount' => $this->mission->documents->where('kind', 'signed')->whereNull('superseded_at')->pluck('participant_id')->unique()->count(),
            'visible' => $this->mission->participants->mapWithKeys(fn ($p) => [$p->id => $svc->canSeeAmount($user, $this->mission, $p)]),
        ]);
    }
}
