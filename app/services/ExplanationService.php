<?php

namespace App\Services;

use App\Mail\ExplanationLetterMail;
use App\Models\Employee;
use App\Models\ExplanationCase;
use App\Models\ExplanationDocument;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\LeaveCalendar;
use App\Support\LeaveVisibility;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Demande d'explication : lettre du chef (ou de la RH) → réponse de l'employé → décision de la RH.
 * La RH est prévenue en même temps que l'employé à chaque étape ; chaque lettre est générée en PDF et jointe au dossier.
 */
class ExplanationService
{
    private const RESOURCE = 'explanation_letters';

    public function __construct(private LetterService $letters, private EmployeeNotifier $notifier)
    {
    }

    /** Date limite de réponse : N jours ouvrés du planning de l'employé, à compter de demain. */
    public function deadlineFor(Employee $employee, int $days): Carbon
    {
        $date = today();

        for ($i = 0; $i < max(1, $days); $i++) {
            $date = LeaveCalendar::nextWorkday($employee->company_id, $date, $employee);
        }

        return $date;
    }

    /** @param array<string, mixed> $d subject, body, response_days, signatory_name, signatory_title, copies */
    public function issue(User $by, Employee $employee, array $d): ExplanationCase
    {
        abort_unless($by->canDo('create', self::RESOURCE) && LeaveVisibility::canSee($by, self::RESOURCE, $employee->id), 403);
        abort_if($by->employee_id && (int) $by->employee_id === $employee->id, 403, 'Vous ne pouvez pas adresser une demande d’explication à vous-même.');

        $subject = trim((string) ($d['subject'] ?? ''));
        $body = trim((string) ($d['body'] ?? ''));
        $days = (int) ($d['response_days'] ?? 0);

        if (mb_strlen($subject) < 5 || mb_strlen($body) < 20) {
            throw ValidationException::withMessages(['explanation' => 'Renseignez l’objet et le corps de la lettre (faits reprochés, date, lieu).']);
        }
        if ($days < 1 || $days > 30) {
            throw ValidationException::withMessages(['explanation' => 'Le délai de réponse doit être compris entre 1 et 30 jours ouvrés.']);
        }

        $case = DB::transaction(function () use ($by, $employee, $d, $subject, $body, $days) {
            $case = ExplanationCase::create([
                'reference' => $this->nextReference($employee->company_id),
                'employee_id' => $employee->id,
                'issued_by_user_id' => $by->id,
                'signatory_name' => trim((string) ($d['signatory_name'] ?? '')) ?: $by->name,
                'signatory_title' => trim((string) ($d['signatory_title'] ?? '')) ?: null,
                'subject' => $subject,
                'body' => $body,
                'copies' => trim((string) ($d['copies'] ?? '')) ?: null,
                'response_days' => $days,
                'response_deadline' => $this->deadlineFor($employee, $days)->toDateString(),
                'status' => 'awaiting_response',
            ]);

            $document = $this->letters->generate($case, 'request', $by, $this->requestData($case, $employee));
            AuditLogger::log('created', self::RESOURCE, $case->id, null, ['reference' => $case->reference, 'employee_id' => $employee->id]);

            DB::afterCommit(fn () => $this->announce($case, $document, $by, 'request'));

            return $case;
        });

        return $case;
    }

    public function markViewed(ExplanationCase $case, User $user): void
    {
        if (! $case->employee_viewed_at && $user->employee_id && (int) $user->employee_id === $case->employee_id) {
            $case->forceFill(['employee_viewed_at' => now()])->save(); // accusé de réception
        }
    }

    /** Réponse écrite de l'employé : lettre PDF envoyée au chef, à la RH et à l'employé. */
    public function respond(ExplanationCase $case, User $user, string $body, ?UploadedFile $attachment = null): ExplanationCase
    {
        abort_unless($user->employee_id && (int) $user->employee_id === $case->employee_id, 403);

        $body = trim($body);

        if (mb_strlen($body) < 10) {
            throw ValidationException::withMessages(['explanation' => 'Rédigez votre réponse (au moins quelques phrases).']);
        }

        $stored = null;

        try {
            return DB::transaction(function () use ($case, $user, $body, $attachment, &$stored) {
                $case = ExplanationCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

                if ($case->status !== 'awaiting_response') {
                    throw ValidationException::withMessages(['explanation' => 'Cette demande n’attend plus de réponse.']);
                }

                if ($attachment) {
                    $stored = $attachment->storeAs("companies/{$case->company_id}/discipline", Str::uuid().'.'.$attachment->extension(), 'local');
                }

                $case->update([
                    'status' => 'responded', 'responded_at' => now(), 'response_body' => $body,
                    'response_attachment_path' => $stored,
                    'response_attachment_name' => $attachment ? Str::limit($attachment->getClientOriginalName(), 150, '') : null,
                ]);

                $employee = Employee::findOrFail($case->employee_id)->loadMissing(['department:id,name', 'position:id,title']);
                $document = $this->letters->generate($case, 'response', $user, $this->responseData($case, $employee));
                AuditLogger::log('responded', self::RESOURCE, $case->id, null, ['late' => $case->response_deadline->lt(today())]);

                DB::afterCommit(fn () => $this->announce($case->fresh('employee'), $document, $user, 'response'));

                return $case;
            });
        } catch (Throwable $e) {
            if ($stored) {
                Storage::disk('local')->delete($stored);
            }

            throw $e;
        }
    }

    /**
     * Décision de la RH après la réponse (ou après le délai si l'employé n'a pas répondu) :
     * classement, mise en garde, avertissement, blâme, mise à pied ou licenciement. La lettre est jointe au dossier.
     *
     * @param array<string, mixed> $d type, subject, body, suspension_start, suspension_end, suspension_paid, dismissal_effective_date
     */
    public function decide(ExplanationCase $case, User $user, array $d): ExplanationCase
    {
        abort_unless(LeaveVisibility::isHr($user, self::RESOURCE), 403);

        $type = (string) ($d['type'] ?? '');
        $subject = trim((string) ($d['subject'] ?? ''));
        $body = trim((string) ($d['body'] ?? ''));
        $fail = fn (string $m) => throw ValidationException::withMessages(['explanation' => $m]);

        if (! config("discipline.decisions.{$type}")) {
            $fail('Choisissez la décision.');
        }
        if (mb_strlen($subject) < 5 || mb_strlen($body) < 20) {
            $fail('Renseignez l’objet et le corps de la lettre de décision.');
        }
        if ($type === 'suspension' && (empty($d['suspension_start']) || empty($d['suspension_end']) || Carbon::parse($d['suspension_end'])->lt(Carbon::parse($d['suspension_start'])))) {
            $fail('Une mise à pied exige une date de début et une date de fin cohérentes.');
        }
        if ($type === 'dismissal' && empty($d['dismissal_effective_date'])) {
            $fail('Un licenciement exige une date d’effet.');
        }

        return DB::transaction(function () use ($case, $user, $type, $subject, $body, $d, $fail) {
            $case = ExplanationCase::whereKey($case->id)->lockForUpdate()->firstOrFail();

            if ($case->status === 'awaiting_response' && ! $case->isOverdue()) {
                $fail('L’employé a encore jusqu’au '.$case->response_deadline->format('d/m/Y').' pour répondre : attendez sa réponse ou la fin du délai.');
            }
            if (! in_array($case->status, ['awaiting_response', 'responded'], true)) {
                $fail('Ce dossier est déjà clos.');
            }

            $case->update([
                'status' => 'decided', 'decision_type' => $type, 'decision_subject' => $subject, 'decision_body' => $body,
                'decided_at' => now(), 'decided_by_user_id' => $user->id,
                'suspension_start' => $type === 'suspension' ? Carbon::parse($d['suspension_start'])->toDateString() : null,
                'suspension_end' => $type === 'suspension' ? Carbon::parse($d['suspension_end'])->toDateString() : null,
                'suspension_paid' => $type === 'suspension' && ! empty($d['suspension_paid']),
                'dismissal_effective_date' => $type === 'dismissal' ? Carbon::parse($d['dismissal_effective_date'])->toDateString() : null,
            ]);

            $employee = Employee::findOrFail($case->employee_id)->loadMissing(['department:id,name', 'position:id,title']);
            $document = $this->letters->generate($case, 'decision', $user, $this->decisionData($case, $employee, $user));
            AuditLogger::log('decided', self::RESOURCE, $case->id, null, ['decision' => $type]);

            DB::afterCommit(fn () => $this->announce($case->fresh('employee'), $document, $user, 'decision'));

            return $case;
        });
    }

    public function withdraw(ExplanationCase $case, User $user, string $reason): ExplanationCase
    {
        abort_unless((int) $case->issued_by_user_id === $user->id || LeaveVisibility::isHr($user, self::RESOURCE), 403);

        if (mb_strlen(trim($reason)) < 3 || $case->status !== 'awaiting_response') {
            throw ValidationException::withMessages(['explanation' => 'Indiquez le motif ; seule une demande sans réponse peut être retirée.']);
        }

        $case->update(['status' => 'withdrawn', 'withdrawn_reason' => trim($reason)]);
        AuditLogger::log('withdrawn', self::RESOURCE, $case->id, null, ['reason' => $reason]);

        $this->notifier->notifyEmployee($case->employee_id, 'Demande d’explication retirée',
            'La demande d’explication réf. '.$case->reference.' est retirée. Aucune réponse n’est attendue.', route('explanations.show', $case->id));
        $this->notifier->notifyUsers($this->hrUsers($case), 'Demande d’explication retirée', 'Réf. '.$case->reference.' retirée : '.trim($reason), route('explanations.show', $case->id));

        return $case;
    }

    /** Rappels automatiques (commande planifiée) : veille de l'échéance → employé ; échéance dépassée → chef et RH. */
    public function remind(): array
    {
        $reminded = $overdue = 0;

        ExplanationCase::withoutGlobalScopes()->where('status', 'awaiting_response')->with('employee')->get()->each(function ($case) use (&$reminded, &$overdue) {
            $employee = Employee::withoutGlobalScopes()->find($case->employee_id);

            if (! $employee) {
                return;
            }

            if (! $case->reminder_sent_at && $case->response_deadline->isSameDay(today()->addDay())) {
                $this->notifier->notifyEmployee($employee, 'Rappel : réponse attendue demain',
                    'Votre réponse à la demande d’explication réf. '.$case->reference.' est attendue au plus tard le '.$case->response_deadline->format('d/m/Y').'.', route('explanations.show', $case->id));
                $case->forceFill(['reminder_sent_at' => now()])->save();
                $reminded++;
            }

            if (! $case->overdue_notified_at && $case->response_deadline->lt(today())) {
                $users = $this->hrUsers($case);

                if ($case->issuer) {
                    $users->push($case->issuer);
                }

                $this->notifier->notifyUsers($users->unique('id'), 'Délai de réponse dépassé',
                    $employee->full_name.' n’a pas répondu à la demande d’explication réf. '.$case->reference.' (échéance : '.$case->response_deadline->format('d/m/Y').').', route('explanations.show', $case->id));
                $case->forceFill(['overdue_notified_at' => now()])->save();
                $overdue++;
            }
        });

        return ['reminded' => $reminded, 'overdue' => $overdue];
    }

    // ---------- Données des lettres ----------

    private function recipientLines(Employee $e): array
    {
        $e->loadMissing(['department:id,name', 'position:id,title']);

        return array_values(array_filter([$e->full_name, 'Matricule : '.$e->matricule, $e->position?->title, $e->department?->name]));
    }

    private function base(ExplanationCase $case): array
    {
        return ['letterhead' => Letterhead::for($case->company_id), 'banner' => null, 'details' => [], 'toLabel' => 'À l’attention de',
            'sender' => null, 'copies' => null, 'deadline' => null, 'deadlineDays' => null, 'showReceipt' => false];
    }

    private function requestData(ExplanationCase $case, Employee $employee): array
    {
        return array_merge($this->base($case), [
            'reference' => $case->reference, 'date' => now(), 'subject' => $case->subject, 'body' => $case->body,
            'recipient' => $this->recipientLines($employee), 'copies' => $case->copies,
            'deadline' => $case->response_deadline, 'deadlineDays' => $case->response_days, 'showReceipt' => true,
            'banner' => 'Demande d’explication',
            'signatoryName' => $case->signatory_name, 'signatoryTitle' => $case->signatory_title,
        ]);
    }

    private function responseData(ExplanationCase $case, Employee $employee): array
    {
        $issuer = $case->signatory_name.($case->signatory_title ? ', '.$case->signatory_title : '');

        return array_merge($this->base($case), [
            'reference' => $case->reference.'-R', 'date' => now(), 'subject' => 'Réponse à la demande d’explication réf. '.$case->reference,
            'body' => $case->response_body, 'sender' => $this->recipientLines($employee),
            'recipient' => [$issuer, 'Direction des Ressources Humaines'], 'toLabel' => 'À l’attention de',
            'banner' => 'Réponse de l’employé', 'copies' => 'Direction des Ressources Humaines',
            'details' => $case->response_attachment_name ? ['Pièce jointe' => $case->response_attachment_name] : [],
            'signatoryName' => $employee->full_name, 'signatoryTitle' => $employee->position?->title,
        ]);
    }

    private function decisionData(ExplanationCase $case, Employee $employee, User $by): array
    {
        $details = [];

        if ($case->decision_type === 'suspension') {
            $details = ['Début de la mise à pied' => $case->suspension_start->format('d/m/Y'), 'Fin de la mise à pied' => $case->suspension_end->format('d/m/Y'),
                'Rémunération' => $case->suspension_paid ? 'Maintenue' : 'Non maintenue'];
        } elseif ($case->decision_type === 'dismissal') {
            $details = ['Date d’effet' => $case->dismissal_effective_date->format('d/m/Y')];
        }

        $signer = $by->employee_id ? Employee::withoutGlobalScopes()->with('position:id,title')->find($by->employee_id) : null;

        return array_merge($this->base($case), [
            'reference' => $case->reference.'-D', 'date' => now(), 'subject' => $case->decision_subject, 'body' => $case->decision_body,
            'recipient' => $this->recipientLines($employee), 'copies' => 'Dossier du personnel',
            'banner' => $case->decisionLabel().' — suite à la demande d’explication réf. '.$case->reference, 'details' => $details, 'showReceipt' => true,
            'signatoryName' => $signer?->full_name ?? $by->name, 'signatoryTitle' => $signer?->position?->title ?? 'Ressources humaines',
        ]);
    }

    // ---------- Notifications et emails ----------

    /** Prévient en même temps l'employé, la RH et l'auteur de la demande (dans l'application, puis par email avec la lettre en PDF). */
    private function announce(ExplanationCase $case, ExplanationDocument $document, User $actor, string $kind): void
    {
        $employee = Employee::withoutGlobalScopes()->find($case->employee_id);
        $company = Letterhead::for($case->company_id)->legal_name;
        $url = route('explanations.show', $case->id);
        $hr = $this->hrUsers($case);
        $issuer = $case->issuer;

        [$title, $messageEmployee, $messageStaff] = match ($kind) {
            'request' => ['Demande d’explication', 'Une demande d’explication (réf. '.$case->reference.') vous a été adressée. Réponse attendue avant le '.$case->response_deadline->format('d/m/Y').'.',
                'Demande d’explication réf. '.$case->reference.' adressée à '.$employee?->full_name.' par '.$case->signatory_name.'. Échéance : '.$case->response_deadline->format('d/m/Y').'.'],
            'response' => ['Réponse à la demande d’explication', 'Votre réponse à la demande réf. '.$case->reference.' a bien été enregistrée et transmise.',
                $employee?->full_name.' a répondu à la demande d’explication réf. '.$case->reference.'.'],
            default => ['Décision — demande d’explication', 'Une décision a été rendue dans le dossier réf. '.$case->reference.' : '.$case->decisionLabel().'.',
                'Décision dans le dossier réf. '.$case->reference.' ('.$employee?->full_name.') : '.$case->decisionLabel().'.'],
        };

        if ($employee) {
            $this->notifier->notifyEmployee($employee, $title, $messageEmployee, $url);
        }

        $staff = $hr->when($issuer, fn (Collection $c) => $c->push($issuer))->unique('id');
        $staff = $staff->reject(fn (User $u) => (int) $u->employee_id === $case->employee_id);
        $this->notifier->notifyUsers($staff, $title, $messageStaff, $url);

        // Emails avec la lettre en pièce jointe : employé, RH, auteur
        $emails = $staff->pluck('email')->filter();

        if ($employee) {
            $emails->push($employee->work_email ?: $employee->personal_email ?: User::where('employee_id', $employee->id)->value('email'));
        }

        $emails = $emails->filter()->unique()->values();

        if ($emails->isNotEmpty()) {
            try {
                Mail::to($emails->all())->send(new ExplanationLetterMail($case->load('employee'), $document, $company));
            } catch (Throwable $e) {
                report($e); // l'échec de l'email n'annule jamais l'action
            }
        }
    }

    /** @return Collection<int, User> utilisateurs RH (droit Modifier + portée Tout), hors l'employé concerné */
    public function hrUsers(ExplanationCase $case): Collection
    {
        return User::where('company_id', $case->company_id)->where('is_active', true)->get()
            ->filter(fn (User $u) => (int) $u->employee_id !== $case->employee_id && LeaveVisibility::isHr($u, self::RESOURCE))->values();
    }

    private function nextReference(int $companyId): string
    {
        $year = now()->year;
        $n = ExplanationCase::withoutGlobalScopes()->where('company_id', $companyId)->whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('DE-%d-%04d', $year, $n++);
        } while (ExplanationCase::withoutGlobalScopes()->where('company_id', $companyId)->where('reference', $reference)->exists());

        return $reference;
    }

    public function canView(User $user, ExplanationCase $case): bool
    {
        return ($user->employee_id && (int) $user->employee_id === $case->employee_id)
            || (int) $case->issued_by_user_id === $user->id
            || LeaveVisibility::isHr($user, self::RESOURCE)
            || ($user->canDo('read', self::RESOURCE) && LeaveVisibility::canSee($user, self::RESOURCE, $case->employee_id));
    }
}
