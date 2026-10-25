<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveSetting;
use App\Models\OvertimeRecord;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Jour de repos (ou férié) travaillé. Après validation, la RH choisit la compensation :
 *  - « recovery » : jour(s) de récupération à prendre avant une date limite ;
 *  - « leave_balance » : ajout direct au solde de congé.
 */
class OvertimeService
{
    public function __construct(
        private LeaveBalanceService $balances,
        private ApprovalService $approvals,
        private EmployeeNotifier $notifier,
    ) {
    }

    public function declare(Employee $employee, string $workedOn, float $days, ?string $reason): OvertimeRecord
    {
        $date = Carbon::parse($workedOn)->startOfDay();

        if ($date->isFuture()) {
            throw ValidationException::withMessages(['overtime' => 'Ce jour n’est pas encore passé.']);
        }
        if ($days <= 0 || $days > 1) {
            throw ValidationException::withMessages(['overtime' => 'Indiquez une durée entre 0,25 et 1 jour.']);
        }
        if (! LeaveCalendar::isRestDay($employee->company_id, $date, $employee)) {
            throw ValidationException::withMessages(['overtime' => 'Ce jour est un jour ouvré normal : seuls les jours de repos ou fériés travaillés se compensent ici.']);
        }

        return DB::transaction(function () use ($employee, $date, $days, $reason) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (OvertimeRecord::where('employee_id', $employee->id)->whereDate('worked_on', $date)->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages(['overtime' => 'Ce jour travaillé est déjà déclaré.']);
            }

            $record = OvertimeRecord::create([
                'employee_id' => $employee->id,
                'worked_on' => $date->toDateString(),
                'days' => $days,
                'reason' => trim((string) $reason) ?: null,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            $this->approvals->start('overtime', $record);
            AuditLogger::log('created', 'overtime', $record->id, null, ['employee_id' => $employee->id, 'worked_on' => $date->toDateString()]);

            if ($step = \App\Models\ApprovalStep::where('approvable_type', 'overtime')->where('approvable_id', $record->id)->where('status', 'pending')->orderBy('position')->first()) {
                $this->approvals->notifyApprovers($step);
            }

            return $record;
        });
    }

    /** @param array<string, mixed> $options compensation, credited_days */
    public function assertCompensationChosen(array $options): void
    {
        if (! in_array($options['compensation'] ?? null, array_keys(OvertimeRecord::COMPENSATIONS), true)) {
            throw ValidationException::withMessages(['decide' => 'Choisissez la compensation : jour de récupération ou ajout au solde de congé.']);
        }

        $days = (float) ($options['credited_days'] ?? 0);

        if ($days <= 0 || $days > 1) {
            throw ValidationException::withMessages(['decide' => 'Indiquez le nombre de jours accordés (entre 0,25 et 1).']);
        }
    }

    public function finalize(OvertimeRecord $record, array $options): void
    {
        $this->assertCompensationChosen($options);

        $employee = Employee::whereKey($record->employee_id)->lockForUpdate()->firstOrFail();
        $days = round((float) $options['credited_days'], 2);
        $compensation = $options['compensation'];
        $expires = null;

        if ($compensation === 'recovery') {
            $expires = today()->addDays(LeaveSetting::for($employee->company_id)->recovery_validity_days)->toDateString();
            $this->balances->post($employee, 'recovery', 'recovery_credit', $days, today(),
                'Jour travaillé le '.$record->worked_on->format('d/m/Y'), ['overtime', $record->id], $expires);
        } else {
            $this->balances->post($employee, 'annual', 'adjustment', $days, today(),
                'Jour travaillé le '.$record->worked_on->format('d/m/Y').' ajouté au solde de congé', ['overtime', $record->id]);
        }

        $record->update([
            'status' => 'approved', 'compensation' => $compensation, 'credited_days' => $days,
            'expires_on' => $expires, 'decided_at' => now(),
        ]);

        $message = $compensation === 'recovery'
            ? "Votre jour travaillé le {$record->worked_on->format('d/m/Y')} vous donne {$days} jour(s) de récupération, à prendre avant le ".Carbon::parse($expires)->format('d/m/Y').'.'
            : "Votre jour travaillé le {$record->worked_on->format('d/m/Y')} a été ajouté à votre solde de congé ({$days} jour(s)).";

        $this->notifier->notifyEmployee($employee, 'Jour travaillé compensé', $message, route('leaves.mine'));
    }

    public function reject(OvertimeRecord $record, ?string $comment): void
    {
        $record->update(['status' => 'rejected', 'decided_at' => now()]);

        $this->notifier->notifyEmployee($record->employee_id, 'Jour travaillé refusé',
            'Votre déclaration du '.$record->worked_on->format('d/m/Y').' n’a pas été retenue. Motif : '.$comment, route('leaves.overtime'));
    }

    public function cancel(OvertimeRecord $record): void
    {
        if ($record->status !== 'pending') {
            throw ValidationException::withMessages(['overtime' => 'Seule une déclaration en attente peut être annulée.']);
        }

        DB::transaction(function () use ($record) {
            \App\Models\ApprovalStep::where('approvable_type', 'overtime')->where('approvable_id', $record->id)
                ->where('status', 'pending')->update(['status' => 'cancelled']);
            $record->update(['status' => 'cancelled']);
        });
    }
}
