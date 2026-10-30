<?php

namespace App\Services;

use App\Enums\MissionStatus;
use App\Models\Mission;
use App\Models\MissionParticipant;
use App\Models\MissionPayment;
use App\Models\User;
use App\Notifications\MissionNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Paiement des perdiems : échéanciers, enregistrement des paiements par la Finance, annulation, reçus.
 * Règle d'or : un paiement enregistré n'est jamais modifié ni supprimé, il est annulé avec un motif.
 */
class MissionPaymentService
{
    public function __construct(private MissionService $missions)
    {
    }

    /* ───────────── Droits ───────────── */

    public function canRecord(User $user): bool
    {
        return $user->canDo('create', 'mission_payments');
    }

    public function canVoid(User $user): bool
    {
        return $user->canDo('update', 'mission_payments');
    }

    /** Qui voit les montants et les paiements d'un participant (même règle que le perdiem). */
    public function canSeeParticipant(User $user, Mission $mission, MissionParticipant $p): bool
    {
        return (int) $mission->company_id === (int) $user->company_id && $this->missions->canSeeAmount($user, $mission, $p);
    }

    public function canAccessReceipt(User $user, Mission $mission, MissionPayment $payment): bool
    {
        if ((int) $payment->mission_id !== (int) $mission->id || ! $payment->receipt_path) {
            return false;
        }

        $p = MissionParticipant::find($payment->participant_id);

        return $p && $this->canSeeParticipant($user, $mission, $p);
    }

    /* ───────────── Échéancier ───────────── */

    /** Après l'approbation du DG : crée l'échéancier par défaut de chaque participant qui n'en a pas. */
    public function createDefaultPlans(Mission $mission): int
    {
        $mission->loadMissing('participants');
        $count = 0;
        $total = 0;

        foreach ($mission->participants as $p) {
            if (MissionPayment::where('participant_id', $p->id)->whereIn('status', ['planned', 'paid'])->exists()) {
                continue;
            }

            $rows = PaymentPlanner::defaultPlan($p->payment_mode, (int) $p->total_amount, $mission->start_date, $mission->end_date);

            if ($rows) {
                $this->insertRows($p, $rows);
                $count++;
                $total += (int) $p->total_amount;
            }
        }

        if ($count) {
            AuditLogger::log('payment_plans_created', 'mission_orders', $mission->id, null, ['participants' => $count]);

            $finance = User::where('company_id', $mission->company_id)->get()->filter(fn (User $u) => $this->canRecord($u));

            if ($finance->isNotEmpty()) {
                Notification::send($finance, new MissionNotification($mission->id, 'Perdiems à payer',
                    "Mission {$mission->reference} approuvée : {$count} participant(s) à payer, pour un total de ".PaymentPlanner::fcfa($total).'.'));
            }
        }

        return $count;
    }

    /** Action RH : remplace les tranches prévues d'un participant (impossible dès qu'un paiement est enregistré). */
    public function setSchedule(Mission $mission, MissionParticipant $participant, array $rows, User $user): void
    {
        abort_unless($this->missions->isHr($user) && (int) $mission->company_id === (int) $user->company_id, 403);
        abort_unless($mission->status === MissionStatus::Approved && (int) $participant->mission_id === (int) $mission->id, 403);

        $total = (int) $participant->total_amount;

        if ($total < 1) {
            throw ValidationException::withMessages(['schedule' => 'Le perdiem de ce participant n’est pas encore défini.']);
        }

        try {
            $clean = PaymentPlanner::normalize($rows, $total);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['schedule' => $e->getMessage()]);
        }

        DB::transaction(function () use ($participant, $clean, $mission) {
            if (MissionPayment::where('participant_id', $participant->id)->where('status', 'paid')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['schedule' => 'Un paiement est déjà enregistré : l’échéancier ne peut plus être modifié.']);
            }

            MissionPayment::where('participant_id', $participant->id)->where('status', 'planned')->delete();
            $this->insertRows($participant, $clean);

            AuditLogger::log('payment_schedule_set', 'mission_orders', $mission->id, null, ['participant_id' => $participant->id, 'rows' => count($clean)]);
        });
    }

    private function insertRows(MissionParticipant $p, array $rows): void
    {
        $sequence = (int) MissionPayment::where('participant_id', $p->id)->max('sequence');

        foreach ($rows as $r) {
            MissionPayment::create([
                'mission_id' => $p->mission_id, 'participant_id' => $p->id, 'sequence' => ++$sequence,
                'label' => $r['label'], 'planned_amount' => $r['amount'], 'planned_date' => $r['date'] ?? null, 'status' => 'planned',
            ]);
        }
    }

    /* ───────────── Paiement ───────────── */

    /** @param array $data paid_amount, paid_at (Y-m-d), method, reference, note */
    public function record(Mission $mission, MissionPayment $payment, User $user, array $data, ?UploadedFile $receipt = null): MissionPayment
    {
        abort_unless($this->canRecord($user) && (int) $mission->company_id === (int) $user->company_id, 403);
        abort_unless((int) $payment->mission_id === (int) $mission->id && $mission->status === MissionStatus::Approved, 403);

        $amount = (int) ($data['paid_amount'] ?? 0);
        $method = (string) ($data['method'] ?? '');
        $reference = trim((string) ($data['reference'] ?? ''));
        $dateRaw = (string) ($data['paid_at'] ?? '');

        if ($amount < 1) {
            throw ValidationException::withMessages(['payment' => 'Saisissez le montant payé.']);
        }

        if (! array_key_exists($method, config('missions.payment_methods'))) {
            throw ValidationException::withMessages(['payment' => 'Choisissez le mode de paiement.']);
        }

        if ($method !== 'cash' && $reference === '') {
            throw ValidationException::withMessages(['payment' => 'La référence de la transaction est obligatoire pour ce mode de paiement.']);
        }

        try {
            $paidAt = CarbonImmutable::parse($dateRaw);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['payment' => 'Date de paiement invalide.']);
        }

        if ($paidAt->startOfDay()->gt(today())) {
            throw ValidationException::withMessages(['payment' => 'La date de paiement ne peut pas être dans le futur.']);
        }

        $paid = DB::transaction(function () use ($mission, $payment, $user, $amount, $method, $reference, $paidAt, $data, $receipt) {
            // Verrou : empêche deux caissiers (ou un double clic) d'enregistrer la même tranche deux fois.
            $p = MissionPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $p || $p->status !== 'planned') {
                throw ValidationException::withMessages(['payment' => 'Cette tranche a déjà été traitée.']);
            }

            if ($amount > (int) $p->planned_amount) {
                throw ValidationException::withMessages(['payment' => 'Le montant payé ne peut pas dépasser le montant prévu de la tranche ('.PaymentPlanner::fcfa((int) $p->planned_amount).').']);
            }

            $participant = MissionParticipant::findOrFail($p->participant_id);
            $paidSoFar = (int) MissionPayment::where('participant_id', $p->participant_id)->where('status', 'paid')->sum('paid_amount');

            if ($paidSoFar + $amount > (int) $participant->total_amount) {
                throw ValidationException::withMessages(['payment' => 'Le total payé dépasserait le perdiem total du participant.']);
            }

            [$path, $name, $hash] = $receipt ? $this->storeReceipt($mission, $p, $receipt) : [null, null, null];

            $p->update([
                'status' => 'paid', 'paid_amount' => $amount, 'paid_at' => $paidAt->toDateString(), 'method' => $method,
                'reference' => $reference ?: null, 'note' => trim((string) ($data['note'] ?? '')) ?: null, 'paid_by' => $user->id,
                'receipt_path' => $path, 'receipt_name' => $name, 'receipt_sha256' => $hash,
            ]);

            // Paiement partiel : le reliquat redevient une tranche à payer (l'échéancier reste cohérent).
            if ($amount < (int) $p->planned_amount) {
                $this->insertRows($participant, [[
                    'label' => 'Reliquat : '.$p->label, 'amount' => (int) $p->planned_amount - $amount, 'date' => $p->planned_date?->toDateString(),
                ]]);
            }

            AuditLogger::log('payment_recorded', 'mission_orders', $mission->id, null, ['payment_id' => $p->id, 'amount' => $amount, 'method' => $method]);

            return $p;
        });

        $this->notifyParticipant($mission, $paid->participant_id, 'Perdiem versé',
            'Un paiement de '.PaymentPlanner::fcfa($amount)." a été enregistré pour la mission {$mission->reference} (".$paidAt->format('d/m/Y').').');

        return $paid;
    }

    public function void(Mission $mission, MissionPayment $payment, User $user, string $reason): void
    {
        abort_unless($this->canVoid($user) && (int) $mission->company_id === (int) $user->company_id, 403);
        abort_unless((int) $payment->mission_id === (int) $mission->id, 403);

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['payment' => 'Indiquez le motif de l’annulation (5 caractères minimum).']);
        }

        $participantId = DB::transaction(function () use ($mission, $payment, $user, $reason) {
            $p = MissionPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $p || $p->status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'Seul un paiement enregistré peut être annulé.']);
            }

            $p->update(['status' => 'voided', 'voided_at' => now(), 'void_reason' => $reason, 'voided_by' => $user->id]);

            // Le montant annulé redevient à payer : le solde reste exact.
            $this->insertRows(MissionParticipant::findOrFail($p->participant_id), [[
                'label' => 'À refaire : '.$p->label, 'amount' => (int) $p->paid_amount, 'date' => $p->planned_date?->toDateString(),
            ]]);

            AuditLogger::log('payment_voided', 'mission_orders', $mission->id, null, ['payment_id' => $p->id, 'reason' => $reason]);

            return $p->participant_id;
        });

        $this->notifyParticipant($mission, $participantId, 'Paiement de perdiem annulé', "Un paiement de la mission {$mission->reference} a été annulé : {$reason}");
    }

    /* ───────────── Reçu signé ───────────── */

    public function attachReceipt(Mission $mission, MissionPayment $payment, UploadedFile $file, User $user): void
    {
        abort_unless($this->canRecord($user) && (int) $mission->company_id === (int) $user->company_id, 403);
        abort_unless((int) $payment->mission_id === (int) $mission->id, 403);

        DB::transaction(function () use ($mission, $payment, $file) {
            $p = MissionPayment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $p || $p->status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'Le reçu se joint à un paiement enregistré.']);
            }

            if ($p->receipt_path) {
                throw ValidationException::withMessages(['payment' => 'Un reçu est déjà joint à ce paiement.']);
            }

            [$path, $name, $hash] = $this->storeReceipt($mission, $p, $file);
            $p->update(['receipt_path' => $path, 'receipt_name' => $name, 'receipt_sha256' => $hash]);

            AuditLogger::log('payment_receipt_attached', 'mission_orders', $mission->id, null, ['payment_id' => $p->id]);
        });
    }

    private function storeReceipt(Mission $mission, MissionPayment $payment, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (! in_array($ext, explode(',', config('missions.signed.mimes')), true) || $file->getSize() > config('missions.signed.max_kb') * 1024) {
            throw ValidationException::withMessages(['payment' => 'Format ou taille du reçu non accepté (PDF, JPG ou PNG, '.(config('missions.signed.max_kb') / 1024).' Mo maximum).']);
        }

        $path = $file->storeAs("companies/{$mission->company_id}/missions/{$mission->id}", "recu-{$payment->participant_id}-{$payment->id}-".Str::uuid().".{$ext}", 'local');

        return [$path, $file->getClientOriginalName(), hash_file('sha256', Storage::disk('local')->path($path))];
    }

    /* ───────────── Outils ───────────── */

    private function notifyParticipant(Mission $mission, int $participantId, string $title, string $message): void
    {
        $employeeId = MissionParticipant::whereKey($participantId)->value('employee_id');
        $users = $employeeId ? User::where('employee_id', $employeeId)->get() : collect();

        if ($users->isNotEmpty()) {
            Notification::send($users, new MissionNotification($mission->id, $title, $message));
        }
    }
}
