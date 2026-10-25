<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveSetting;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Décompte des jours selon les règles de l'entreprise (mode de décompte) et le planning de l'employé
 * (semaine standard ou rotation). Jours fériés : propres à chaque entreprise.
 */
final class LeaveCalendar
{
    /** @return array<int, string> dates (Y-m-d) des jours fériés entre deux dates */
    public static function holidays(int $companyId, Carbon $from, Carbon $to): array
    {
        return Holiday::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    /** Planning de l'employé ; à défaut, la semaine de travail standard de l'entreprise. */
    public static function scheduleFor(int $companyId, ?Employee $employee = null): WorkSchedule
    {
        if ($employee && $employee->work_schedule_id) {
            $schedule = WorkSchedule::withoutGlobalScopes()->where('company_id', $companyId)->find($employee->work_schedule_id);

            if ($schedule) {
                return $schedule;
            }
        }

        return WorkSchedule::standard(LeaveSetting::for($companyId)->workdayList());
    }

    /** Jour de travail prévu ? Une rotation ignore les jours fériés (on travaille selon le cycle) ; la semaine standard les exclut. */
    public static function isScheduled(WorkSchedule $schedule, Carbon $day, array $holidays): bool
    {
        if (! $schedule->isWorkday($day)) {
            return false;
        }

        return $schedule->type === 'rotation' || ! in_array($day->toDateString(), $holidays, true);
    }

    /** Un jour de repos (hors planning) ou férié : un jour qu'on ne devrait pas travailler. */
    public static function isRestDay(int $companyId, Carbon $day, ?Employee $employee = null): bool
    {
        return ! self::isScheduled(self::scheduleFor($companyId, $employee), $day, self::holidays($companyId, $day, $day));
    }

    public static function modeLabel(?int $companyId): string
    {
        $mode = $companyId ? (LeaveSetting::for($companyId)->count_mode ?: 'calendar') : 'calendar';

        return LeaveSetting::COUNT_MODES[$mode] ?? $mode;
    }

    /**
     * Nombre de jours décomptés pour une période. Mode : celui de l'entreprise, sauf $mode imposé
     * (une absence se décompte toujours sur les jours de travail prévus : 'scheduled').
     * startHalf = on commence l'après-midi ; endHalf = on termine à midi.
     */
    public static function countDays(int $companyId, Carbon $start, Carbon $end, bool $startHalf = false, bool $endHalf = false, ?Employee $employee = null, ?string $mode = null): float
    {
        if ($end->lt($start)) {
            throw new InvalidArgumentException('La date de fin précède la date de début.');
        }
        if ($start->equalTo($end) && $startHalf && $endHalf) {
            throw new InvalidArgumentException('Choisissez le matin ou l’après-midi, pas les deux.');
        }

        $mode ??= (LeaveSetting::for($companyId)->count_mode ?: 'calendar');
        $schedule = self::scheduleFor($companyId, $employee);
        $holidays = self::holidays($companyId, $start, $end);

        $counts = fn (Carbon $d): bool => match ($mode) {
            'calendar' => true,
            'calendar_no_holidays' => ! in_array($d->toDateString(), $holidays, true),
            default => self::isScheduled($schedule, $d, $holidays),
        };

        $days = 0.0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($counts($d)) {
                $days += 1;
            }
        }

        if ($start->equalTo($end)) {
            return $counts($start) && ($startHalf || $endHalf) ? 0.5 : $days;
        }

        if ($startHalf && $counts($start)) {
            $days -= 0.5;
        }
        if ($endHalf && $counts($end)) {
            $days -= 0.5;
        }

        return max(0.0, $days);
    }

    /** Premier jour de travail prévu après $date : la date de reprise. */
    public static function nextWorkday(int $companyId, Carbon $date, ?Employee $employee = null): Carbon
    {
        $schedule = self::scheduleFor($companyId, $employee);
        $next = $date->copy()->addDay();
        $holidays = self::holidays($companyId, $next, $next->copy()->addDays(120));

        for ($i = 0; $i < 120; $i++) {
            if (self::isScheduled($schedule, $next, $holidays)) {
                return $next;
            }
            $next->addDay();
        }

        return $date->copy()->addDay();
    }
}
