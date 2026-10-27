<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveSetting;
use App\Models\PermissionRequest;
use App\Models\PermissionType;
use App\Models\User;
use App\Support\LeaveCalendar;
use App\Support\LeaveVisibility;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

/**
 * Permissions d'absence : jours ou heures, avec quota gratuit paramétrable par type et règle pour l'excédent
 * (retiré du congé annuel, refusé, ou non payé). Chaîne de validation commune (chef puis RH).
 */
class PermissionService
{
    public function __construct(
        private LeaveBalanceService $balances,
        private ApprovalService $approvals,
        private EmployeeNotifier $notifier,
    ) {
    }

    /**
     * Aperçu et contrôle d'une demande (sans l'enregistrer).
     *
     * @param  array<string, mixed>  $in  start_date, end_date, start_time, end_time
     * @return array{days: float, hours: ?float, free: float, deducted: float, unpaid: float, errors: array<int, string>, available: ?float, after: ?float, exception: bool, tolerance: float}
     */
    public function evaluate(Employee $employee, ?PermissionType $type, array $in, bool $asHr = false): array
    {
        $out = ['days' => 0.0, 'hours' => null, 'free' => 0.0, 'deducted' => 0.0, 'unpaid' => 0.0, 'errors' => [], 'available' => null, 'after' => null, 'exception' => false, 'tolerance' => 0.0];

        if (! $type || empty($in['start_date'])) {
            return $out;
        }

        $perDay = (float) config('permission_rules.hours_per_day', 8);

        try {
            $s = Carbon::parse($in['start_date'])->startOfDay();

            if ($type->kind === 'hours') {
                if (empty($in['start_time']) || empty($in['end_time'])) {
                    return $out;
                }

                $a = Carbon::parse($in['start_date'].' '.$in['start_time']);
                $b = Carbon::parse($in['start_date'].' '.$in['end_time']);

                if ($b->lte($a)) {
                    $out['errors'][] = 'L’heure de fin doit être après l’heure de début.';

                    return $out;
                }

                $e = $s->copy();
                $out['hours'] = round($a->diffInMinutes($b) / 60, 2);
                $days = round($out['hours'] / $perDay, 2);
            } else {
                if (empty($in['end_date'])) {
                    return $out;
                }

                $e = Carbon::parse($in['end_date'])->startOfDay();
                $days = LeaveCalendar::countDays($employee->company_id, $s, $e, false, false, $employee);
            }
        } catch (InvalidArgumentException $ex) {
            $out['errors'][] = $ex->getMessage();

            return $out;
        } catch (Throwable) {
            $out['errors'][] = 'Dates ou heures invalides.';

            return $out;
        }

        $out['days'] = $days;

        if ($s->lt(today()) && ! $asHr) {
            $out['errors'][] = 'La date est passée : seule la RH peut enregistrer une permission rétroactive.';
        }
        if ($days <= 0) {
            $out['errors'][] = 'Cette période ne contient aucun jour décompté.';
        }
        if ($type->kind === 'days' && $type->max_days !== null && $days > $type->max_days) {
            $out['errors'][] = 'Ce type de permission est limité à '.$this->fmt($type->max_days).' jour(s).';
        }

        // Une permission d'heures peut coexister avec d'autres permissions d'heures, pas avec un congé ni une permission de jours.
        $overlap = PermissionRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $e)->whereDate('end_date', '>=', $s)
            ->whereHas('type', fn ($t) => $t->withoutGlobalScopes()->where('kind', 'days'))->exists()
            || LeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $e)->whereDate('end_date', '>=', $s)->exists();

        if ($overlap) {
            $out['errors'][] = 'Une autre demande (congé ou permission) existe déjà sur cette période.';
        }

        if ($type->yearly_cap_days !== null) {
            $used = (float) PermissionRequest::where('employee_id', $employee->id)->where('permission_type_id', $type->id)
                ->whereIn('status', ['pending', 'approved'])->whereYear('start_date', $s->year)->sum('days');

            if ($used + $days > $type->yearly_cap_days) {
                $out['errors'][] = 'Plafond annuel dépassé pour ce type : '.$this->fmt($used).' j déjà demandés sur '.$this->fmt($type->yearly_cap_days).' j.';
            }
        }

        // Quota gratuit, puis excédent
        if ($type->kind === 'hours') {
            if ($type->hours_free_per_month === null) {
                $excess = 0.0;
            } else {
                $used = (float) PermissionRequest::where('employee_id', $employee->id)->where('permission_type_id', $type->id)
                    ->whereIn('status', ['pending', 'approved'])->whereYear('start_date', $s->year)->whereMonth('start_date', $s->month)->sum('hours');
                $excess = round(max(0.0, $out['hours'] - max(0.0, $type->hours_free_per_month - $used)) / $perDay, 2);
            }
        } else {
            $excess = round(max(0.0, $days - $type->free_days), 2);
        }

        $out['free'] = round($days - $excess, 2);

        if ($excess > 0) {
            if ($type->excess_policy === 'refuse') {
                $out['errors'][] = 'Au-delà de '.($type->kind === 'hours' ? $this->fmt((float) $type->hours_free_per_month).' h par mois' : $this->fmt($type->free_days).' jour(s)').', ce type de permission est refusé : faites plutôt une demande de congé.';
            } elseif ($type->excess_policy === 'unpaid') {
                $out['unpaid'] = $excess;
            } else {
                $out['deducted'] = $excess;
                $this->balances->sync($employee);
                $available = $this->balances->available($employee->id);
                $after = round($available - $excess, 2);
                $tolerance = LeaveSetting::for($employee->company_id)->negative_tolerance;

                $out['available'] = $available;
                $out['after'] = $after;
                $out['tolerance'] = $tolerance;

                if ($after < 0) {
                    if ($after >= -$tolerance) {
                        $out['exception'] = true;
                    } else {
                        $out['errors'][] = 'Solde de congé insuffisant pour l’excédent de '.$this->fmt($excess).' j ('.$this->fmt($available).' j disponibles, tolérance '.$this->fmt($tolerance).' j).';
                    }
                }
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $data */
    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null, bool $asHr = false): PermissionRequest
    {
        $stored = null;

        try {
            return DB::transaction(function () use ($employee, $data, $attachment, $asHr, &$stored) {
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $type = PermissionType::where('active', true)->find($data['permission_type_id'] ?? 0);

                if (! $type) {
                    throw ValidationException::withMessages(['permission' => 'Type de permission inconnu ou désactivé.']);
                }

                $ev = $this->evaluate($employee, $type, $data, $asHr);

                if ($ev['errors']) {
                    throw ValidationException::withMessages(['permission' => implode(' ', $ev['errors'])]);
                }
                if ($type->requires_document && ! $attachment) {
                    throw ValidationException::withMessages(['permission' => 'Un justificatif est obligatoire pour ce type de permission.']);
                }
                if ($ev['exception'] && mb_strlen(trim((string) ($data['exception_reason'] ?? ''))) < 5) {
                    throw ValidationException::withMessages(['permission' => 'L’excédent dépasse votre solde de congé : expliquez pourquoi, par exception.']);
                }

                if ($attachment) {
                    $stored = $attachment->storeAs('companies/'.$employee->company_id.'/permissions', Str::uuid().'.'.$attachment->extension(), 'local');
                }

                $hours = $type->kind === 'hours';
                $start = Carbon::parse($data['start_date'])->toDateString();

                $request = PermissionRequest::create([
                    'employee_id' => $employee->id,
                    'permission_type_id' => $type->id,
                    'start_date' => $start,
                    'end_date' => $hours ? $start : Carbon::parse($data['end_date'])->toDateString(),
                    'start_time' => $hours ? $data['start_time'] : null,
                    'end_time' => $hours ? $data['end_time'] : null,
                    'days' => $ev['days'],
                    'hours' => $ev['hours'],
                    'free_days' => $ev['free'],
                    'deducted_days' => $ev['deducted'],
                    'unpaid_days' => $ev['unpaid'],
                    'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
                    'exception_reason' => $ev['exception'] ? trim((string) $data['exception_reason']) : null,
                    'exceeds_balance' => $ev['exception'],
                    'balance_before' => $ev['available'],
                    'attachment_path' => $stored,
                    'attachment_name' => $attachment ? Str::limit($attachment->getClientOriginalName(), 150, '') : null,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                $this->approvals->start('permission', $request);
                AuditLogger::log('created', 'absence_permissions', $request->id, null, ['employee_id' => $employee->id, 'days' => $ev['days'], 'deducted' => $ev['deducted']]);

                if ($step = ApprovalStep::where('approvable_type', 'permission')->where('approvable_id', $request->id)->where('status', 'pending')->orderBy('position')->first()) {
                    $this->approvals->notifyApprovers($step);
                }

                return $request;
            });
        } catch (Throwable $e) {
            if ($stored) {
                Storage::disk('local')->delete($stored);
            }

            throw $e;
        }
    }

    /** Revérification du solde à la validation RH (il a pu changer depuis la demande). */
    public function assertCanApprove(PermissionRequest $request): void
    {
        if ($request->deducted_days <= 0) {
            return;
        }

        $employee = Employee::findOrFail($request->employee_id);
        $this->balances->sync($employee);

        $after = round($this->balances->balance($employee->id) - $request->deducted_days, 2);
        $tolerance = LeaveSetting::for($employee->company_id)->negative_tolerance;

        if ($after < -$tolerance) {
            throw ValidationException::withMessages([
                'decide' => 'Validation impossible : le solde de congé de l’employé ne couvre plus l’excédent ('.$this->fmt($after).' j après déduction, tolérance '.$this->fmt($tolerance).' j).',
            ]);
        }
    }

    /** Validation finale (dans la transaction de décision) : retire l'excédent du congé, puis fiche de permission et notification. */
    public function finalize(PermissionRequest $request): void
    {
        $employee = Employee::whereKey($request->employee_id)->lockForUpdate()->firstOrFail();
        $after = null;

        if ($request->deducted_days > 0) {
            $this->balances->sync($employee);
            $after = round($this->balances->balance($employee->id) - $request->deducted_days, 2);
            $this->balances->post($employee, 'annual', 'taken', -$request->deducted_days, $request->start_date,
                'Excédent de permission '.$request->periodLabel(), ['permission_request', $request->id]);
        }

        $request->update(['status' => 'approved', 'decided_at' => now(), 'balance_after' => $after]);

        $approver = auth()->user();

        DB::afterCommit(function () use ($request, $approver) {
            try {
                app(PermissionSheetService::class)->issue($request->fresh(['employee', 'type', 'steps.decider']), $approver);
            } catch (Throwable $e) {
                report($e);
                $this->notifier->notifyEmployee($request->employee_id, 'Permission accordée',
                    'Votre permission '.$request->periodLabel().' est accordée.', route('permissions.show', $request->id));
            }
        });
    }

    public function cancel(PermissionRequest $request, User $by): PermissionRequest
    {
        return DB::transaction(function () use ($request, $by) {
            $request = PermissionRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $isOwner = $by->employee_id && (int) $by->employee_id === $request->employee_id;
            $isHr = LeaveVisibility::isHr($by, 'absence_permissions');

            if ($request->status === 'pending') {
                abort_unless($isOwner || $isHr, 403);
            } elseif ($request->status === 'approved') {
                abort_unless($isHr, 403);

                if ($request->start_date->lte(today())) {
                    throw ValidationException::withMessages(['permission' => 'Cette permission a déjà commencé : ajustez le solde manuellement si nécessaire.']);
                }
            } else {
                throw ValidationException::withMessages(['permission' => 'Cette demande ne peut plus être annulée.']);
            }

            ApprovalStep::where('approvable_type', 'permission')->where('approvable_id', $request->id)->where('status', 'pending')->update(['status' => 'cancelled']);

            if ($request->status === 'approved' && $request->deducted_days > 0) {
                $this->balances->post(Employee::findOrFail($request->employee_id), 'annual', 'refund', $request->deducted_days, today(),
                    'Annulation de la permission '.$request->periodLabel(), ['permission_request', $request->id]);
            }

            $request->update(['status' => 'cancelled', 'decided_at' => now()]);
            AuditLogger::log('cancelled', 'absence_permissions', $request->id, null, ['by' => $by->id]);

            return $request;
        });
    }

    public function canView(User $user, PermissionRequest $request): bool
    {
        if (($user->employee_id && (int) $user->employee_id === $request->employee_id)
            || LeaveVisibility::canSee($user, 'absence_permissions', $request->employee_id)) {
            return true;
        }

        $employee = $request->employee;

        if (! $employee || ! $user->employee_id) {
            return false;
        }

        $side = $this->approvals->managerSide($employee);

        return ($side['manager'] && $side['manager']->id === (int) $user->employee_id)
            || in_array((int) $user->employee_id, $side['delegates'], true);
    }

    public function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',') ?: '0';
    }
}
