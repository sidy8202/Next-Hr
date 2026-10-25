<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveSetting;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Soldes de congé. Le solde est TOUJOURS la somme du registre (table leave_ledger) : jamais une valeur écrasée.
 * Fonctionne sans utilisateur connecté (commande planifiée) : aucun filtre automatique, tout passe par l'employé.
 */
class LeaveBalanceService
{
    public function monthlyRate(Employee $employee): float
    {
        return $employee->leave_days_per_month !== null
            ? (float) $employee->leave_days_per_month
            : LeaveSetting::for($employee->company_id)->default_monthly_days;
    }

    /**
     * Met le registre à jour : acquisitions mensuelles manquantes + expiration des jours de récupération.
     * Idempotent : peut être appelé à chaque affichage.
     */
    public function sync(Employee $employee): void
    {
        $this->accrue($employee);
        $this->expireRecovery($employee);
    }

    /**
     * Acquisition des jours de congé, selon le mode de l'entreprise :
     *  - monthly : « cadence » × mois de présence révolus (+ majoration d'ancienneté, ex. +2 j/an après 15 ans) ;
     *  - per_worked_days : « cadence » jours acquis par tranche de N jours réellement travaillés au planning de l'employé (rotations).
     */
    private function accrue(Employee $employee): void
    {
        if ($employee->status !== 'active' || ! $employee->hire_date) {
            return;
        }

        $hire = Carbon::parse($employee->hire_date)->startOfDay();
        $start = Carbon::parse($employee->leave_accrual_from ?? $employee->hire_date)->startOfDay();
        $today = today();

        if ($start->gt($today)) {
            return;
        }

        // Rotation « sur site » : le repos remplace le congé annuel, aucune acquisition.
        if (LeaveCalendar::scheduleFor($employee->company_id, $employee)->grants_annual_leave === false) {
            return;
        }

        $setting = LeaveSetting::for($employee->company_id);
        $rate = $this->monthlyRate($employee);
        $existing = DB::table('leave_ledger')->where('employee_id', $employee->id)->where('type', 'accrual')->pluck('period_key')->all();
        $rows = [];

        $row = fn (string $key, float $days, Carbon $date, string $note) => [
            'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'pool' => 'annual', 'type' => 'accrual',
            'days' => $days, 'effective_date' => $date->toDateString(), 'period_key' => $key, 'note' => $note, 'created_at' => now(),
        ];

        if ($setting->accrual_mode === 'per_worked_days') {
            $schedule = LeaveCalendar::scheduleFor($employee->company_id, $employee);
            $holidays = LeaveCalendar::holidays($employee->company_id, $start, $today);
            $per = max(1, (int) $setting->accrual_per_days);
            $worked = 0;
            $block = 0;

            for ($d = $start->copy(); $d->lt($today); $d->addDay()) {
                if (! LeaveCalendar::isScheduled($schedule, $d, $holidays)) {
                    continue;
                }

                if (++$worked % $per === 0) {
                    $block++;

                    if (! in_array("w{$block}", $existing, true)) {
                        $rows[] = $row("w{$block}", round($rate, 2), $d->copy()->addDay(), "Acquisition : {$per} jours travaillés");
                    }
                }
            }
        } else {
            $months = self::monthsBetween($start, $today);
            $bonuses = $setting->bonusRules();

            for ($k = 1; $k <= $months; $k++) {
                if (in_array("m{$k}", $existing, true)) {
                    continue;
                }

                $date = $start->copy()->addMonthsNoOverflow($k);
                $years = intdiv(self::monthsBetween($hire, $date), 12); // ancienneté réelle (depuis l'embauche)
                $bonus = collect($bonuses)->filter(fn ($b) => $years >= (int) $b[0])->max(fn ($b) => (float) $b[1]) ?? 0;

                $rows[] = $row("m{$k}", round($rate + $bonus / 12, 2), $date,
                    $bonus > 0 ? "Acquisition mensuelle (dont majoration d'ancienneté : +{$bonus} j/an)" : 'Acquisition mensuelle');
            }
        }

        if ($rows) {
            DB::table('leave_ledger')->insertOrIgnore($rows); // la contrainte d'unicité protège de deux exécutions simultanées
        }
    }

    /** Mois entiers écoulés entre deux dates. */
    private static function monthsBetween(Carbon $from, Carbon $to): int
    {
        $months = ($to->year - $from->year) * 12 + ($to->month - $from->month);

        while ($months > 0 && $from->copy()->addMonthsNoOverflow($months)->gt($to)) {
            $months--;
        }

        return max(0, $months);
    }

    /** Les jours de récupération non pris à leur date limite expirent (les plus anciens sont consommés en premier). */
    private function expireRecovery(Employee $employee): void
    {
        $rows = DB::table('leave_ledger')->where('employee_id', $employee->id)->where('pool', 'recovery')->orderBy('id')->get();

        $credits = $rows->filter(fn ($r) => (float) $r->days > 0 && $r->type !== 'expiry')
            ->sortBy(fn ($r) => ($r->expires_on ?? '9999-12-31').'#'.str_pad((string) $r->id, 12, '0', STR_PAD_LEFT))->values();

        if ($credits->isEmpty()) {
            return;
        }

        $debits = $rows->filter(fn ($r) => (float) $r->days < 0 && $r->type !== 'expiry')->sum(fn ($r) => abs((float) $r->days));
        $alreadyExpired = $rows->where('type', 'expiry')->pluck('reference_id')->all();
        $new = [];

        foreach ($credits as $credit) {
            $used = min($debits, (float) $credit->days);
            $debits -= $used;
            $remaining = round((float) $credit->days - $used, 2);

            if ($remaining > 0 && $credit->expires_on && $credit->expires_on < today()->toDateString() && ! in_array($credit->id, $alreadyExpired, false)) {
                $new[] = [
                    'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'pool' => 'recovery',
                    'type' => 'expiry', 'days' => -$remaining, 'effective_date' => today()->toDateString(),
                    'reference_type' => 'ledger', 'reference_id' => $credit->id, 'period_key' => 'x'.$credit->id,
                    'note' => 'Jours de récupération expirés', 'created_at' => now(),
                ];
            }
        }

        if ($new) {
            DB::table('leave_ledger')->insertOrIgnore($new);
        }
    }

    public function balance(int $employeeId, string $pool = 'annual'): float
    {
        return round((float) DB::table('leave_ledger')->where('employee_id', $employeeId)->where('pool', $pool)->sum('days'), 2);
    }

    /** Jours déjà demandés mais pas encore validés : ils sont réservés, pour qu'on ne dépense jamais deux fois le même jour. */
    public function reserved(int $employeeId, string $pool = 'annual'): float
    {
        return round((float) LeaveRequest::withoutGlobalScopes()
            ->where('employee_id', $employeeId)->where('status', 'pending')
            ->whereHas('type', fn ($q) => $q->withoutGlobalScopes()->where('pool', $pool))
            ->sum('days'), 2);
    }

    public function available(int $employeeId, string $pool = 'annual'): float
    {
        return round($this->balance($employeeId, $pool) - $this->reserved($employeeId, $pool), 2);
    }

    /**
     * Ajoute une écriture au registre.
     *
     * @param  array{0: string, 1: int}|null  $reference
     */
    public function post(Employee $employee, string $pool, string $type, float $days, Carbon|string $date, ?string $note = null, ?array $reference = null, ?string $expiresOn = null): void
    {
        DB::table('leave_ledger')->insert([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'pool' => $pool,
            'type' => $type,
            'days' => round($days, 2),
            'effective_date' => Carbon::parse($date)->toDateString(),
            'expires_on' => $expiresOn,
            'reference_type' => $reference[0] ?? null,
            'reference_id' => $reference[1] ?? null,
            'note' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /** @return Collection<int, object> dernières écritures, la plus récente d'abord */
    public function ledger(int $employeeId, int $limit = 20): Collection
    {
        return DB::table('leave_ledger')->where('employee_id', $employeeId)->orderByDesc('effective_date')->orderByDesc('id')->limit($limit)->get();
    }

    /** Jours de récupération encore disponibles et leur date limite (les plus proches d'abord). */
    public function recoveryCredits(int $employeeId): Collection
    {
        $rows = DB::table('leave_ledger')->where('employee_id', $employeeId)->where('pool', 'recovery')->get();
        $debits = $rows->filter(fn ($r) => (float) $r->days < 0)->sum(fn ($r) => abs((float) $r->days));

        return $rows->filter(fn ($r) => (float) $r->days > 0)
            ->sortBy(fn ($r) => ($r->expires_on ?? '9999-12-31').'#'.str_pad((string) $r->id, 12, '0', STR_PAD_LEFT))
            ->map(function ($r) use (&$debits) {
                $used = min($debits, (float) $r->days);
                $debits -= $used;

                return ['remaining' => round((float) $r->days - $used, 2), 'expires_on' => $r->expires_on];
            })
            ->filter(fn ($c) => $c['remaining'] > 0)->values();
    }
}
