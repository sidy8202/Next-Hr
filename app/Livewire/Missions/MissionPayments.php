<?php

namespace App\Livewire\Missions;

use App\Models\Mission;
use App\Services\MissionPaymentService;
use App\Services\MissionService;
use App\Services\PaymentPlanner;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Panneau « Paiements des perdiems » de la page d'une mission.
 * Chacun n'y voit que ce que son rôle lui permet : Finance, RH, DG = tout ; participant = ses paiements ;
 * demandeur = son département. L'enregistrement des paiements est réservé à la Finance.
 */
class MissionPayments extends Component
{
    use WithFileUploads;

    public int $missionId;

    // Enregistrement d'un paiement
    public ?int $payingId = null;
    public array $pay = [];
    public $receipt = null;

    // Annulation d'un paiement
    public ?int $voidingId = null;
    public string $voidReason = '';

    // Édition de l'échéancier (RH)
    public ?int $schedulingFor = null;
    public array $scheduleRows = [];

    // Reçus joints après coup, par paiement
    public array $lateReceipts = [];

    public function mount(int $missionId): void
    {
        $this->missionId = $missionId;
        abort_unless(app(MissionService::class)->canView(auth()->user(), $this->mission), 403);
    }

    #[Computed]
    public function mission(): Mission
    {
        return Mission::with(['participants.employee', 'participants.department', 'payments'])->findOrFail($this->missionId);
    }

    private function svc(): MissionPaymentService
    {
        return app(MissionPaymentService::class);
    }

    private function refresh(): void
    {
        unset($this->mission);
    }

    private function resetForms(): void
    {
        $this->reset(['payingId', 'pay', 'receipt', 'voidingId', 'voidReason', 'schedulingFor', 'scheduleRows']);
        $this->resetErrorBag();
    }

    /* ───────────── Paiement ───────────── */

    public function openPay(int $paymentId): void
    {
        abort_unless($this->svc()->canRecord(auth()->user()), 403);
        $p = $this->mission->payments->firstWhere('id', $paymentId);
        abort_unless($p && $p->status === 'planned', 404);

        $this->resetForms();
        $this->payingId = $p->id;
        $this->pay = ['amount' => (string) $p->planned_amount, 'date' => now()->toDateString(), 'method' => 'cash', 'reference' => '', 'note' => ''];
    }

    public function recordPayment(): void
    {
        $this->validate(
            ['receipt' => ['nullable', 'file', 'mimes:'.config('missions.signed.mimes'), 'max:'.config('missions.signed.max_kb')]],
            [], ['receipt' => 'reçu'],
        );

        $payment = $this->mission->payments->firstWhere('id', $this->payingId);
        abort_unless($payment, 404);

        $ok = $this->guarded(fn () => $this->svc()->record($this->mission, $payment, auth()->user(), [
            'paid_amount' => $this->pay['amount'] ?? 0, 'paid_at' => $this->pay['date'] ?? '', 'method' => $this->pay['method'] ?? '',
            'reference' => $this->pay['reference'] ?? '', 'note' => $this->pay['note'] ?? '',
        ], $this->receipt));

        if (! $ok) {
            return;
        }

        $this->resetForms();
        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Paiement enregistré : le bénéficiaire est notifié.');
    }

    public function openVoid(int $paymentId): void
    {
        abort_unless($this->svc()->canVoid(auth()->user()), 403);
        $this->resetForms();
        $this->voidingId = $paymentId;
    }

    public function confirmVoid(): void
    {
        $payment = $this->mission->payments->firstWhere('id', $this->voidingId);
        abort_unless($payment, 404);

        if (! $this->guarded(fn () => $this->svc()->void($this->mission, $payment, auth()->user(), $this->voidReason))) {
            return;
        }

        $this->resetForms();
        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Paiement annulé : le montant redevient à payer.');
    }

    public function attachReceipt(int $paymentId): void
    {
        $this->validate(
            ["lateReceipts.{$paymentId}" => ['required', 'file', 'mimes:'.config('missions.signed.mimes'), 'max:'.config('missions.signed.max_kb')]],
            [], ["lateReceipts.{$paymentId}" => 'reçu'],
        );

        $payment = $this->mission->payments->firstWhere('id', $paymentId);
        abort_unless($payment, 404);
        $file = $this->lateReceipts[$paymentId];

        if (! $this->guarded(fn () => $this->svc()->attachReceipt($this->mission, $payment, $file, auth()->user()))) {
            return;
        }

        unset($this->lateReceipts[$paymentId]);
        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Reçu joint au paiement.');
    }

    /* ───────────── Échéancier (RH) ───────────── */

    public function openSchedule(int $participantId): void
    {
        abort_unless(app(MissionService::class)->isHr(auth()->user()), 403);
        $p = $this->mission->participants->firstWhere('id', $participantId);
        abort_unless($p, 404);

        $this->resetForms();
        $this->schedulingFor = $p->id;

        $existing = $this->mission->payments->where('participant_id', $p->id)->where('status', 'planned')->sortBy('sequence');

        $this->scheduleRows = $existing->isNotEmpty()
            ? $existing->map(fn ($r) => ['label' => $r->label, 'amount' => (string) $r->planned_amount, 'date' => $r->planned_date?->toDateString() ?? ''])->values()->all()
            : collect(PaymentPlanner::defaultPlan($p->payment_mode, (int) $p->total_amount, $this->mission->start_date, $this->mission->end_date))
                ->map(fn ($r) => ['label' => $r['label'], 'amount' => (string) $r['amount'], 'date' => $r['date']])->all();
    }

    public function addScheduleRow(): void
    {
        $this->scheduleRows[] = ['label' => '', 'amount' => '', 'date' => ''];
    }

    public function removeScheduleRow(int $index): void
    {
        unset($this->scheduleRows[$index]);
        $this->scheduleRows = array_values($this->scheduleRows);
    }

    public function saveSchedule(): void
    {
        $participant = $this->mission->participants->firstWhere('id', $this->schedulingFor);
        abort_unless($participant, 404);

        if (! $this->guarded(fn () => $this->svc()->setSchedule($this->mission, $participant, $this->scheduleRows, auth()->user()))) {
            return;
        }

        $this->resetForms();
        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Échéancier enregistré.');
    }

    public function cancelForm(): void
    {
        $this->resetForms();
    }

    private function guarded(callable $action): bool
    {
        try {
            $action();

            return true;
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('payment', config('app.debug') ? 'Action impossible : '.$e->getMessage() : 'Action impossible : rien n’a été enregistré.');

            return false;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $svc = $this->svc();
        $mission = $this->mission;
        $rows = [];

        foreach ($mission->participants as $p) {
            if (! $svc->canSeeParticipant($user, $mission, $p)) {
                continue;
            }

            $payments = $mission->payments->where('participant_id', $p->id)->whereIn('status', ['planned', 'paid', 'voided'])->sortBy(['sequence'])->values();

            $rows[] = [
                'participant' => $p,
                'payments' => $payments,
                'summary' => PaymentPlanner::summarize(
                    (int) $p->total_amount,
                    (int) $payments->where('status', 'paid')->sum('paid_amount'),
                    $payments->where('status', 'planned')->count(),
                ),
                'hasPaid' => $payments->where('status', 'paid')->isNotEmpty(),
            ];
        }

        return view('livewire.missions.mission-payments', [
            'mission' => $mission,
            'rows' => $rows,
            'canRecord' => $svc->canRecord($user),
            'canVoid' => $svc->canVoid($user),
            'isHr' => app(MissionService::class)->isHr($user),
            'methods' => config('missions.payment_methods'),
        ]);
    }
}
