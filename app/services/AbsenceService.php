<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\LeaveSetting;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** Absences : une absence NON justifiée est retirée du solde de congé ; si elle est justifiée ensuite, les jours sont rendus. */
class AbsenceService
{
    public function __construct(private LeaveBalanceService $balances, private EmployeeNotifier $notifier)
    {
    }

    public function record(Employee $employee, string $start, string $end, bool $justified, ?string $reason = null): Absence
    {
        try {
            $days = LeaveCalendar::countDays($employee->company_id, Carbon::parse($start), Carbon::parse($end), false, false, $employee, 'scheduled');
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['absence' => $e->getMessage()]);
        }

        if ($days <= 0) {
            throw ValidationException::withMessages(['absence' => 'Cette période ne contient aucun jour ouvré.']);
        }

        $absence = DB::transaction(function () use ($employee, $start, $end, $justified, $reason, $days) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $deduct = ! $justified && LeaveSetting::for($employee->company_id)->deduct_unjustified;

            $absence = Absence::create([
                'employee_id' => $employee->id,
                'start_date' => Carbon::parse($start)->toDateString(),
                'end_date' => Carbon::parse($end)->toDateString(),
                'days' => $days,
                'justified' => $justified,
                'reason' => $this->clean($reason),
                'deducted_days' => $deduct ? $days : 0,
                'justified_at' => $justified ? now() : null,
                'recorded_by' => auth()->id(),
            ]);

            if ($deduct) {
                $this->balances->post($employee, 'annual', 'unjustified_absence', -$days, $absence->start_date,
                    'Absence non justifiée '.$this->period($absence), ['absence', $absence->id]);
            }

            AuditLogger::log('created', 'absences', $absence->id, null, ['employee_id' => $employee->id, 'days' => $days, 'justified' => $justified]);

            return $absence;
        });

        $this->notifier->notifyEmployee($employee,
            $justified ? 'Absence enregistrée' : 'Absence non justifiée enregistrée',
            'Une absence '.($justified ? 'justifiée' : 'non justifiée').' '.$this->period($absence).' ('.$days.' j) a été enregistrée à votre dossier.'
                .($absence->deducted_days > 0 ? ' Elle est retirée de votre solde de congé.' : ' Vous pouvez la justifier auprès des ressources humaines.'),
            route('leaves.mine'));

        return $absence;
    }

    /** Un justificatif est fourni : l'absence devient justifiée et les jours retirés sont rendus. */
    public function justify(Absence $absence): Absence
    {
        return DB::transaction(function () use ($absence) {
            $absence = Absence::whereKey($absence->id)->lockForUpdate()->firstOrFail();

            if ($absence->justified) {
                return $absence;
            }

            $employee = Employee::findOrFail($absence->employee_id);

            if ($absence->deducted_days > 0) {
                $this->balances->post($employee, 'annual', 'refund', $absence->deducted_days, today(),
                    'Absence justifiée '.$this->period($absence), ['absence', $absence->id]);
            }

            $refunded = $absence->deducted_days;
            $absence->update(['justified' => true, 'justified_at' => now(), 'deducted_days' => 0]);
            AuditLogger::log('justified', 'absences', $absence->id, null, ['refunded' => $refunded]);

            $this->notifier->notifyEmployee($employee, 'Absence justifiée',
                'Votre absence '.$this->period($absence).' est justifiée.'.($refunded > 0 ? " {$refunded} j ont été rendus à votre solde." : ''), route('leaves.mine'));

            return $absence;
        });
    }

    public function delete(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $absence = Absence::whereKey($absence->id)->lockForUpdate()->firstOrFail();

            if ($absence->deducted_days > 0) {
                $this->balances->post(Employee::findOrFail($absence->employee_id), 'annual', 'refund', $absence->deducted_days, today(),
                    'Absence supprimée '.$this->period($absence), ['absence', $absence->id]);
            }

            AuditLogger::log('deleted', 'absences', $absence->id, ['employee_id' => $absence->employee_id], null);
            $absence->delete();
        });
    }

    private function period(Absence $a): string
    {
        return $a->start_date->equalTo($a->end_date) ? 'le '.$a->start_date->format('d/m/Y') : 'du '.$a->start_date->format('d/m/Y').' au '.$a->end_date->format('d/m/Y');
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }
}
