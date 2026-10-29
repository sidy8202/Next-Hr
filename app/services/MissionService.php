<?php

namespace App\Services;

use App\Enums\MissionRole;
use App\Enums\MissionStatus;
use App\Enums\PerdiemMode;
use App\Models\Employee;
use App\Models\Mission;
use App\Models\MissionApproval;
use App\Models\MissionParticipant;
use App\Models\User;
use App\Notifications\MissionNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class MissionService
{
    /* ───────────── Droits ───────────── */

    public function isHr(User $user): bool
    {
        return $user->canDo('update', 'mission_orders_hr');
    }

    public function isDg(User $user): bool
    {
        return $user->canDo('update', 'mission_orders_dg');
    }

    /**
     * Visibilité (la portée own/team/all de la matrice est IGNORÉE pour cette ressource) :
     * - le demandeur voit ses missions ;
     * - la RH et le DG voient tout, sauf les brouillons des autres ;
     * - un participant voit les missions APPROUVÉES où il figure.
     */
    public function visibleQuery(User $user): Builder
    {
        return Mission::query()
            ->where('company_id', $user->company_id)
            ->where(function (Builder $w) use ($user) {
                $w->where('requested_by', $user->id);

                if ($this->isHr($user) || $this->isDg($user)) {
                    $w->orWhere('status', '!=', MissionStatus::Draft->value);
                }

                if ($user->employee_id) {
                    // Mission approuvée : visible par ses participants et par le chef direct d'un participant.
                    $w->orWhere(fn (Builder $x) => $x
                        ->where('status', MissionStatus::Approved->value)
                        ->whereHas('participants', fn ($p) => $p
                            ->where('employee_id', $user->employee_id)
                            ->orWhereHas('employee', fn ($e) => $e->where('manager_id', $user->employee_id))));
                }
            });
    }

    public function canView(User $user, Mission $mission): bool
    {
        return $this->visibleQuery($user)->whereKey($mission->id)->exists();
    }

    public function canEdit(User $user, Mission $mission): bool
    {
        return (int) $mission->requested_by === $user->id && $mission->status->isEditableByRequester();
    }

    public function canCancel(User $user, Mission $mission): bool
    {
        if (in_array($mission->status, [MissionStatus::Rejected, MissionStatus::Cancelled], true)) {
            return false;
        }

        if ($this->isHr($user)) {
            return true;
        }

        return (int) $mission->requested_by === $user->id && $mission->status !== MissionStatus::Approved;
    }

    /** Qui voit le perdiem d'un participant : RH, DG, le participant lui-même, le demandeur pour son propre département. */
    public function canSeeAmount(User $user, Mission $mission, MissionParticipant $p): bool
    {
        if ($this->isHr($user) || $this->isDg($user)) {
            return true;
        }

        if ($user->employee_id && (int) $user->employee_id === (int) $p->employee_id) {
            return true;
        }

        return (int) $mission->requested_by === $user->id && (int) $p->department_id === (int) $mission->department_id;
    }

    /* ───────────── Création / modification par le demandeur ───────────── */

    /**
     * @param array $data  purpose, destination_zone, lodging, start_date, end_date, program
     * @param array $rows  employee_id, role, perdiem_mode, daily_rate, payment_mode
     */
    public function save(User $user, ?Mission $mission, array $data, array $rows, bool $submit): Mission
    {
        $requester = $user->employee_id ? Employee::withoutGlobalScopes()->whereNull('deleted_at')->find($user->employee_id) : null;

        if (! $requester) {
            throw ValidationException::withMessages(['mission' => 'Votre compte n’est lié à aucun employé : impossible de déterminer le département demandeur.']);
        }

        if ($mission && ! $this->canEdit($user, $mission)) {
            abort(403);
        }

        $start = \Carbon\CarbonImmutable::parse($data['start_date']);
        $end = \Carbon\CarbonImmutable::parse($data['end_date']);
        $days = PerdiemCalculator::days($start, $end); // lève si retour < départ

        if (count($rows) < 1) {
            throw ValidationException::withMessages(['participants' => 'Ajoutez au moins un participant.']);
        }

        $ids = array_map(fn ($r) => (int) $r['employee_id'], $rows);

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['participants' => 'Un employé figure deux fois dans la liste.']);
        }

        if (collect($rows)->where('role', MissionRole::Chief->value)->count() > 1) {
            throw ValidationException::withMessages(['participants' => 'Une mission n’a qu’un seul chef de mission.']);
        }

        $employees = Employee::withoutGlobalScopes()->whereNull('deleted_at')
            ->where('company_id', $user->company_id)->where('status', 'active')
            ->whereIn('id', $ids)->get()->keyBy('id');

        if ($employees->count() !== count($ids)) {
            throw ValidationException::withMessages(['participants' => 'Un participant est introuvable ou n’est plus actif.']);
        }

        $this->assertNoOverlap($ids, $start, $end, $mission?->id, $employees);

        return DB::transaction(function () use ($user, $mission, $data, $rows, $submit, $requester, $employees, $days) {
            $payload = [
                'purpose' => $data['purpose'],
                'destination_zone' => $data['destination_zone'],
                'lodging' => $data['lodging'] ?: null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'program' => $data['program'] ?: null,
            ];

            if ($mission) {
                $mission->update($payload);
            } else {
                $mission = Mission::create($payload + [
                    'company_id' => $user->company_id,
                    'requested_by' => $user->id,
                    'department_id' => $requester->department_id,
                    'status' => MissionStatus::Draft->value,
                ]);
            }

            $existing = $mission->participants()->get()->keyBy('employee_id');

            foreach ($rows as $row) {
                $emp = $employees[(int) $row['employee_id']];
                $ownDept = (int) $emp->department_id === (int) $mission->department_id;
                $p = $existing[$emp->id] ?? new MissionParticipant(['mission_id' => $mission->id, 'employee_id' => $emp->id]);

                $p->department_id = $emp->department_id;
                $p->role = MissionRole::tryFrom($row['role'])?->value ?? 'member';
                $p->payment_mode = array_key_exists($row['payment_mode'], config('missions.payment_modes')) ? $row['payment_mode'] : 'after';
                $p->days = $days;

                // Règle de confidentialité appliquée CÔTÉ SERVEUR :
                // le demandeur ne fixe un perdiem que pour son propre département.
                if ($ownDept) {
                    $rate = (int) ($row['daily_rate'] ?? 0);
                    if (($row['perdiem_mode'] ?? '') === PerdiemMode::Manual->value && $rate > 0) {
                        $p->perdiem_mode = PerdiemMode::Manual->value;
                        $p->daily_rate = $rate;
                    } else {
                        $p->perdiem_mode = PerdiemMode::ToDefine->value;
                        $p->daily_rate = null;
                    }
                } elseif (! $p->exists) {
                    $p->perdiem_mode = PerdiemMode::ToDefine->value;
                    $p->daily_rate = null;
                } // sinon : on conserve ce que la RH avait défini pour ce participant hors département

                $p->total_amount = null;
                $p->rate_locked_at = null;
                $p->save();
            }

            $mission->participants()->whereNotIn('employee_id', array_keys($employees->all()))->delete();

            if ($submit) {
                $this->submit($mission);
            }

            AuditLogger::log($submit ? 'submitted' : 'saved', 'mission_orders', $mission->id, null, ['status' => $mission->status->value]);

            return $mission->refresh();
        });
    }

    private function submit(Mission $mission): void
    {
        $mission->update([
            'status' => MissionStatus::Submitted->value,
            'submitted_at' => now(),
            'reference' => $mission->reference ?: $this->nextReference($mission->company_id),
        ]);

        $this->notify($this->usersWhere($mission->company_id, fn (User $u) => $this->isHr($u)), $mission,
            'Nouvelle mission à vérifier', "Mission {$mission->reference} : {$mission->destination_zone}, du {$mission->start_date->format('d/m/Y')} au {$mission->end_date->format('d/m/Y')}.");

        // Information (sans lien) aux chefs des participants d'autres départements.
        $outside = $mission->participants()->with('employee:id,manager_id,first_name,last_name')
            ->where('department_id', '!=', $mission->department_id)->get();

        foreach ($outside as $p) {
            if ($p->employee?->manager_id) {
                $this->notify(User::where('employee_id', $p->employee->manager_id)->get(), $mission,
                    'Un de vos employés est proposé pour une mission',
                    "{$p->employee->full_name} est proposé pour la mission {$mission->reference} ({$mission->destination_zone}), en attente de validation.", false);
            }
        }
    }

    /* ───────────── Étape RH ───────────── */

    /** @param array $review  [participant_id => perdiem_mode, days, daily_rate, salary_divisor, payment_mode] */
    public function hrDecide(Mission $mission, User $user, string $decision, ?string $comment, array $review = []): Mission
    {
        abort_unless($this->isHr($user), 403);
        abort_unless($mission->company_id === $user->company_id && $mission->status === MissionStatus::Submitted, 403);
        $this->assertDecision($decision, $comment);

        return DB::transaction(function () use ($mission, $user, $decision, $comment, $review) {
            if ($decision === 'approved') {
                $this->lockPerdiems($mission, $review);
            }

            $new = match ($decision) {
                'approved' => MissionStatus::HrApproved,
                'returned' => MissionStatus::Returned,
                'rejected' => MissionStatus::Rejected,
            };

            $mission->update(['status' => $new->value]);
            $this->recordApproval($mission, $user, 'hr', $decision, $comment);

            if ($decision === 'approved') {
                $this->notify($this->usersWhere($mission->company_id, fn (User $u) => $this->isDg($u)), $mission,
                    'Mission à approuver', "Mission {$mission->reference} validée par la RH, en attente de votre approbation.");
            } else {
                $this->notifyRequester($mission, $decision, $comment);
            }

            return $mission->refresh();
        });
    }

    private function lockPerdiems(Mission $mission, array $review): void
    {
        foreach ($mission->participants()->with('employee')->get() as $p) {
            $r = $review[$p->id] ?? null;
            $name = $p->employee?->full_name ?? "participant #{$p->id}";

            if (! $r) {
                throw ValidationException::withMessages(['review' => "Perdiem non renseigné pour {$name}."]);
            }

            $days = (int) ($r['days'] ?? 0);
            if ($days < 1 || $days > 366) {
                throw ValidationException::withMessages(['review' => "Nombre de jours invalide pour {$name}."]);
            }

            $mode = PerdiemMode::tryFrom($r['perdiem_mode'] ?? '');
            $divisor = null;

            if ($mode === PerdiemMode::Manual) {
                $daily = (int) ($r['daily_rate'] ?? 0);
                if ($daily < 1) {
                    throw ValidationException::withMessages(['review' => "Saisissez le perdiem journalier de {$name}."]);
                }
            } elseif ($mode === PerdiemMode::DailySalary) {
                $divisor = (int) ($r['salary_divisor'] ?? config('missions.salary_divisor'));
                $salary = $this->monthlySalaryFor($p->employee);
                if ($salary === null) {
                    throw ValidationException::withMessages(['review' => "Salaire de référence introuvable pour {$name} : choisissez un montant manuel."]);
                }
                $daily = PerdiemCalculator::dailyFromMonthlySalary($salary, $divisor);
            } else {
                throw ValidationException::withMessages(['review' => "Définissez le perdiem de {$name} (manuel ou salaire journalier)."]);
            }

            $pay = $r['payment_mode'] ?? $p->payment_mode;

            $p->update([
                'perdiem_mode' => $mode->value,
                'days' => $days,
                'daily_rate' => $daily,
                'salary_divisor' => $divisor,
                'total_amount' => PerdiemCalculator::total($daily, $days),
                'payment_mode' => array_key_exists($pay, config('missions.payment_modes')) ? $pay : $p->payment_mode,
                'rate_locked_at' => now(),
            ]);
        }
    }

    /**
     * Salaire mensuel de référence (entier, FCFA) = base_salary du contrat le plus récent de l'employé.
     * (Les bornes salary_min / salary_max du poste sont une fourchette, pas le salaire de la personne : non utilisées.)
     * À AFFINER quand tu auras défini ce qu'est le « contrat actif » (statut, dates).
     */
    protected function monthlySalaryFor(?Employee $employee): ?int
    {
        if (! $employee) {
            return null;
        }

        $value = $employee->contracts()->latest('id')->value('base_salary');

        return $value !== null ? (int) round((float) $value) : null;
    }

    /* ───────────── Étape DG ───────────── */

    public function dgDecide(Mission $mission, User $user, string $decision, ?string $comment): Mission
    {
        abort_unless($this->isDg($user), 403);
        abort_unless($mission->company_id === $user->company_id && $mission->status === MissionStatus::HrApproved, 403);
        $this->assertDecision($decision, $comment);

        $result = DB::transaction(function () use ($mission, $user, $decision, $comment) {
            $new = match ($decision) {
                'approved' => MissionStatus::Approved,
                'returned' => MissionStatus::Returned,
                'rejected' => MissionStatus::Rejected,
            };

            $mission->update(['status' => $new->value]);
            $this->recordApproval($mission, $user, 'dg', $decision, $comment);

            if ($decision === 'approved') {
                $this->notifyRequester($mission, 'approved', null);
                $this->notify($this->usersWhere($mission->company_id, fn (User $u) => $this->isHr($u)), $mission,
                    'Mission approuvée : ordre à signer', "Mission {$mission->reference} approuvée par la direction. Les ordres individuels sont générés : à imprimer, faire signer puis déposer signés.");
            } else {
                $this->notifyRequester($mission, $decision, $comment);
            }

            return $mission->refresh();
        });

        if ($decision === 'approved') {
            try {
                app(MissionDocumentService::class)->generateOrders($result);
            } catch (Throwable $e) {
                report($e); // la RH peut relancer la génération depuis la page de la mission
            }
        }

        return $result;
    }

    /* ───────────── Annulation ───────────── */

    public function cancel(Mission $mission, User $user, string $reason): Mission
    {
        abort_unless($mission->company_id === $user->company_id && $this->canCancel($user, $mission), 403);

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['cancel_reason' => 'Indiquez le motif de l’annulation (5 caractères minimum).']);
        }

        $wasApproved = $mission->status === MissionStatus::Approved;

        $mission->update(['status' => MissionStatus::Cancelled->value, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        $mission->documents()->update(['voided_at' => now()]);
        AuditLogger::log('cancelled', 'mission_orders', $mission->id, null, ['reason' => $reason]);

        if ($wasApproved) {
            $users = User::where('company_id', $mission->company_id)
                ->whereIn('employee_id', $mission->participants()->pluck('employee_id'))->get()
                ->merge(User::whereKey($mission->requested_by)->get());
            $this->notify($users, $mission, 'Mission annulée', "La mission {$mission->reference} est annulée : {$reason}");
        }

        return $mission->refresh();
    }

    /* ───────────── Outils internes ───────────── */

    private function assertDecision(string $decision, ?string $comment): void
    {
        if (! in_array($decision, ['approved', 'returned', 'rejected'], true)) {
            abort(422);
        }

        if ($decision !== 'approved' && mb_strlen(trim((string) $comment)) < 5) {
            throw ValidationException::withMessages(['comment' => 'Le motif est obligatoire pour un retour ou un rejet (5 caractères minimum).']);
        }
    }

    private function recordApproval(Mission $mission, User $user, string $step, string $decision, ?string $comment): void
    {
        MissionApproval::create([
            'mission_id' => $mission->id, 'user_id' => $user->id, 'step' => $step,
            'decision' => $decision, 'comment' => $comment ? trim($comment) : null, 'decided_at' => now(),
        ]);

        AuditLogger::log($decision, 'mission_orders', $mission->id, null, ['step' => $step]);
    }

    private function assertNoOverlap(array $employeeIds, $start, $end, ?int $ignoreMissionId, Collection $employees): void
    {
        $clash = MissionParticipant::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereHas('mission', fn ($m) => $m
                ->whereIn('status', [MissionStatus::Submitted->value, MissionStatus::HrApproved->value, MissionStatus::Approved->value])
                ->when($ignoreMissionId, fn ($q) => $q->where('id', '!=', $ignoreMissionId))
                ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start))
            ->with('mission:id,reference')->get();

        if ($clash->isNotEmpty()) {
            $p = $clash->first();
            $name = $employees[$p->employee_id]->full_name ?? 'Un participant';
            throw ValidationException::withMessages(['participants' => "{$name} est déjà affecté à la mission {$p->mission->reference} sur ces dates."]);
        }

        // Congé approuvé qui chevauche la mission (statut supposé : 'approved').
        $leave = \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->first();

        if ($leave) {
            $name = $employees[$leave->employee_id]->full_name ?? 'Un participant';
            throw ValidationException::withMessages(['participants' => "{$name} a un congé approuvé du {$leave->start_date->format('d/m/Y')} au {$leave->end_date->format('d/m/Y')} qui chevauche la mission."]);
        }
    }

    private function nextReference(int $companyId): string
    {
        $year = now()->year;
        $prefix = config('missions.reference_prefix', 'OM')."-{$year}-";

        DB::table('companies')->where('id', $companyId)->lockForUpdate()->first(); // sérialise l'attribution

        $count = Mission::withTrashed()->where('company_id', $companyId)->where('reference', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function usersWhere(int $companyId, callable $filter): Collection
    {
        return User::where('company_id', $companyId)->get()->filter($filter)->values();
    }

    private function notifyRequester(Mission $mission, string $decision, ?string $comment): void
    {
        [$title, $msg] = match ($decision) {
            'approved' => ['Mission approuvée', "Votre mission {$mission->reference} est approuvée. L’ordre signé vous sera transmis."],
            'returned' => ['Mission à corriger', "Votre mission {$mission->reference} vous est renvoyée : {$comment}"],
            default => ['Mission rejetée', "Votre mission {$mission->reference} est rejetée : {$comment}"],
        };

        $this->notify(User::whereKey($mission->requested_by)->get(), $mission, $title, $msg);
    }

    private function notify(iterable $users, Mission $mission, string $title, string $message, bool $withLink = true): void
    {
        $users = collect($users)->unique('id')->values();

        if ($users->isNotEmpty()) {
            Notification::send($users, new MissionNotification($mission->id, $title, $message, $withLink));
        }
    }
}
