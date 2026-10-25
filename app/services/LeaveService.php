<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveSetting;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class LeaveService
{
    public function __construct(
        private LeaveBalanceService $balances,
        private ApprovalService $approvals,
        private EmployeeNotifier $notifier,
    ) {
    }

    /**
     * Évalue une demande sans l'enregistrer (aperçu en direct du formulaire ET contrôle à l'envoi).
     *
     * @return array{days: float, errors: array<int, string>, available: ?float, after: ?float, exception: bool, tolerance: float, pool: ?string}
     */
    public function evaluate(Employee $employee, ?LeaveType $type, ?string $start, ?string $end, bool $startHalf = false, bool $endHalf = false, bool $asHr = false): array
    {
        $out = ['days' => 0.0, 'errors' => [], 'available' => null, 'after' => null, 'exception' => false, 'tolerance' => 0.0, 'pool' => $type?->pool];

        if (! $type || ! $start || ! $end) {
            return $out;
        }

        try {
            $s = Carbon::parse($start)->startOfDay();
            $e = Carbon::parse($end)->startOfDay();
            $days = LeaveCalendar::countDays($employee->company_id, $s, $e, $startHalf, $endHalf, $employee);
        } catch (InvalidArgumentException $ex) {
            $out['errors'][] = $ex->getMessage();

            return $out;
        } catch (Throwable) {
            $out['errors'][] = 'Dates invalides.';

            return $out;
        }

        $out['days'] = $days;

        if ($s->lt(today()) && ! $asHr) {
            $out['errors'][] = 'La date de début est passée : seule la RH peut enregistrer un congé rétroactif.';
        }
        if ($days <= 0) {
            $out['errors'][] = 'Cette période ne contient aucun jour ouvré.';
        }
        if ($type->max_days !== null && $days > $type->max_days) {
            $out['errors'][] = 'Ce type de congé est limité à '.rtrim(rtrim(number_format($type->max_days, 1, ',', ''), '0'), ',').' jour(s).';
        }

        $overlap = LeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $e)->whereDate('end_date', '>=', $s)->exists();

        if ($overlap) {
            $out['errors'][] = 'Une autre demande existe déjà sur cette période.';
        }

        if ($type->pool && $days > 0) {
            $this->balances->sync($employee);
            $available = $this->balances->available($employee->id, $type->pool);
            $after = round($available - $days, 2);
            $tolerance = $type->pool === 'annual' ? LeaveSetting::for($employee->company_id)->negative_tolerance : 0.0;

            $out['available'] = $available;
            $out['after'] = $after;
            $out['tolerance'] = $tolerance;

            if ($after < 0) {
                if ($after >= -$tolerance) {
                    $out['exception'] = true; // toléré par exception : motif obligatoire, la RH le verra
                } else {
                    $out['errors'][] = 'Solde insuffisant : '.$this->fmt($available).' j disponible(s) pour '.$this->fmt($days)
                        .' j demandé(s)'.($tolerance > 0 ? ' (tolérance maximale : '.$this->fmt($tolerance).' j).' : '.');
                }
            }
        }

        return $out;
    }

    /** Dépose une demande et lance sa validation. @param array<string, mixed> $data */
    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null, bool $asHr = false): LeaveRequest
    {
        $stored = null;

        try {
            $leave = DB::transaction(function () use ($employee, $data, $attachment, $asHr, &$stored) {
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail(); // une demande à la fois par employé : pas de double dépense

                $type = LeaveType::where('active', true)->find($data['leave_type_id'] ?? 0);

                if (! $type) {
                    throw ValidationException::withMessages(['leave' => 'Type de congé inconnu ou désactivé.']);
                }

                $ev = $this->evaluate($employee, $type, $data['start_date'] ?? null, $data['end_date'] ?? null, ! empty($data['start_half']), ! empty($data['end_half']), $asHr);

                if ($ev['errors']) {
                    throw ValidationException::withMessages(['leave' => implode(' ', $ev['errors'])]);
                }
                if ($type->requires_document && ! $attachment) {
                    throw ValidationException::withMessages(['leave' => 'Un justificatif est obligatoire pour ce type de congé.']);
                }
                if ($ev['exception'] && mb_strlen(trim((string) ($data['exception_reason'] ?? ''))) < 5) {
                    throw ValidationException::withMessages(['leave' => 'Votre solde est dépassé : expliquez pourquoi vous demandez ce congé par exception.']);
                }

                $interim = ! empty($data['interim_employee_id']) ? (int) $data['interim_employee_id'] : null;

                if ($interim && ($interim === $employee->id || ! Employee::whereKey($interim)->exists())) {
                    throw ValidationException::withMessages(['leave' => 'L’intérimaire choisi est invalide.']);
                }

                if ($attachment) {
                    $stored = $attachment->storeAs('companies/'.$employee->company_id.'/leaves', Str::uuid().'.'.$attachment->extension(), 'local');
                }

                $leave = LeaveRequest::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'start_date' => Carbon::parse($data['start_date'])->toDateString(),
                    'end_date' => Carbon::parse($data['end_date'])->toDateString(),
                    'start_half' => ! empty($data['start_half']),
                    'end_half' => ! empty($data['end_half']),
                    'days' => $ev['days'],
                    'reason' => $this->clean($data['reason'] ?? null),
                    'exception_reason' => $ev['exception'] ? $this->clean($data['exception_reason'] ?? null) : null,
                    'exceeds_balance' => $ev['exception'],
                    'balance_before' => $ev['available'],
                    'interim_employee_id' => $interim,
                    'attachment_path' => $stored,
                    'attachment_name' => $attachment ? Str::limit($attachment->getClientOriginalName(), 150, '') : null,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                $this->approvals->start('leave', $leave);

                AuditLogger::log('created', 'leave_requests', $leave->id, null, [
                    'employee_id' => $employee->id, 'days' => $ev['days'], 'period' => $leave->periodLabel(),
                ]);

                $first = $leave->steps()->where('status', 'pending')->first();

                if ($first) {
                    $this->approvals->notifyApprovers($first);
                }

                return $leave;
            });
        } catch (Throwable $e) {
            if ($stored) {
                Storage::disk('local')->delete($stored); // pas de fichier orphelin
            }

            throw $e;
        }

        return $leave;
    }

    /** Dernier contrôle de solde au moment de la validation finale (le solde a pu changer depuis la demande). */
    public function assertCanApprove(LeaveRequest $leave): void
    {
        $type = $leave->type;

        if (! $type?->pool) {
            return;
        }

        $employee = Employee::findOrFail($leave->employee_id);
        $this->balances->sync($employee);

        $after = round($this->balances->balance($employee->id, $type->pool) - $leave->days, 2);
        $tolerance = $type->pool === 'annual' ? LeaveSetting::for($employee->company_id)->negative_tolerance : 0.0;

        if ($after < -$tolerance) {
            throw ValidationException::withMessages([
                'decide' => 'Validation impossible : le solde de l’employé ne couvre plus ce congé (solde après congé : '.$this->fmt($after).' j, tolérance : '.$this->fmt($tolerance).' j).',
            ]);
        }
    }

    /** Validation finale (dans la transaction de décision) : retire les jours, puis fiche de congé et notification. */
    public function finalize(LeaveRequest $leave): void
    {
        $employee = Employee::whereKey($leave->employee_id)->lockForUpdate()->firstOrFail();
        $type = $leave->type;
        $after = null;

        if ($type?->pool) {
            $this->balances->sync($employee);
            $after = round($this->balances->balance($employee->id, $type->pool) - $leave->days, 2);

            $this->balances->post($employee, $type->pool, 'taken', -$leave->days, $leave->start_date,
                $type->name.' '.$leave->periodLabel(), ['leave_request', $leave->id]);
        }

        $leave->update(['status' => 'approved', 'decided_at' => now(), 'balance_after' => $after]);

        $approver = auth()->user();

        DB::afterCommit(function () use ($leave, $approver) {
            try {
                app(LeaveSheetService::class)->issue($leave->fresh(['employee', 'type', 'steps.decider', 'interim']), $approver);
            } catch (Throwable $e) {
                report($e);

                $this->notifier->notifyEmployee($leave->employee_id, 'Congé validé',
                    'Votre congé '.$leave->periodLabel().' a été validé. Bonne jouissance de votre congé !', route('leaves.show', $leave->id));
            }
        });
    }

    public function cancel(LeaveRequest $leave, User $by): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $by) {
            $leave = LeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail();
            $isOwner = $by->employee_id && (int) $by->employee_id === $leave->employee_id;
            $isHr = \App\Support\LeaveVisibility::isHr($by, 'leave_requests');

            if ($leave->status === 'pending') {
                abort_unless($isOwner || $isHr, 403);
            } elseif ($leave->status === 'approved') {
                abort_unless($isHr, 403);

                if ($leave->start_date->lte(today())) {
                    throw ValidationException::withMessages(['leave' => 'Ce congé a déjà commencé : ajustez le solde manuellement si nécessaire.']);
                }
            } else {
                throw ValidationException::withMessages(['leave' => 'Cette demande ne peut plus être annulée.']);
            }

            $wasApproved = $leave->status === 'approved';

            \App\Models\ApprovalStep::where('approvable_type', 'leave')->where('approvable_id', $leave->id)
                ->where('status', 'pending')->update(['status' => 'cancelled']);

            if ($wasApproved && $leave->type?->pool) {
                $this->balances->post(Employee::findOrFail($leave->employee_id), $leave->type->pool, 'refund', $leave->days, today(),
                    'Annulation du congé '.$leave->periodLabel(), ['leave_request', $leave->id]);
            }

            $leave->update(['status' => 'cancelled', 'decided_at' => now()]);
            AuditLogger::log('cancelled', 'leave_requests', $leave->id, null, ['by' => $by->id]);

            if (! $isOwner) {
                $this->notifier->notifyEmployee($leave->employee_id, 'Congé annulé',
                    'Votre congé '.$leave->periodLabel().' a été annulé par les ressources humaines.', route('leaves.show', $leave->id));
            }

            return $leave;
        });
    }

    /** Ajustement manuel du solde (solde initial, correction) : motif obligatoire, écriture au registre. */
    public function adjust(Employee $employee, string $pool, float $days, string $note): void
    {
        abort_unless(in_array($pool, ['annual', 'recovery'], true), 422);

        if ($days == 0.0 || mb_strlen(trim($note)) < 3) {
            throw ValidationException::withMessages(['adjust' => 'Indiquez un nombre de jours (positif ou négatif) et un motif.']);
        }

        DB::transaction(function () use ($employee, $pool, $days, $note) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $this->balances->post($employee, $pool, 'adjustment', $days, today(), trim($note));
            AuditLogger::log('balance_adjusted', 'leave_requests', $employee->id, null, ['pool' => $pool, 'days' => $days, 'note' => $note]);
        });

        $this->notifier->notifyEmployee($employee, 'Solde de congé ajusté',
            'Votre solde de '.($pool === 'annual' ? 'congé' : 'récupération').' a été ajusté de '.($days > 0 ? '+' : '').$this->fmt($days).' j. Motif : '.trim($note));
    }

    /** Qui peut voir une demande : l'employé, son chef ou intérimaire, et ceux dont la portée de lecture le couvre. */
    public function canView(User $user, LeaveRequest $leave): bool
    {
        if ($user->employee_id && (int) $user->employee_id === $leave->employee_id) {
            return true;
        }
        if (\App\Support\LeaveVisibility::canSee($user, 'leave_requests', $leave->employee_id)) {
            return true;
        }

        $employee = $leave->employee;

        if (! $employee || ! $user->employee_id) {
            return false;
        }

        $side = $this->approvals->managerSide($employee);

        return ($side['manager'] && $side['manager']->id === (int) $user->employee_id)
            || in_array((int) $user->employee_id, $side['delegates'], true);
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    public function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',') ?: '0';
    }
}
