<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewService
{
    /** Planifie un entretien ; une candidature en « Présélection » passe automatiquement en « Entretien ». */
    public function schedule(Application $application, array $data): Interview
    {
        return DB::transaction(function () use ($application, $data) {
            $app = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            if (in_array($app->stage, ['hired', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'interview' => 'Cette candidature est terminée : on ne peut plus y planifier d’entretien.',
                ]);
            }

            [$start, $minutes] = $this->window($data);
            $this->assertFree($app, $data['interviewer_id'] ?? null, $start, $minutes, null);

            $interview = Interview::create([
                'application_id' => $app->id,
                'scheduled_at' => $start,
                'duration_minutes' => $minutes,
                'mode' => $data['mode'],
                'location' => $this->clean($data['location'] ?? null),
                'interviewer_id' => ! empty($data['interviewer_id']) ? (int) $data['interviewer_id'] : null,
                'status' => 'scheduled',
                'created_by' => auth()->id(),
            ]);

            if ($app->stage === 'screening') {
                app(ApplicationService::class)->move($app, 'interview', 'Entretien planifié le '.$start->format('d/m/Y H:i'));
            }

            AuditLogger::log('scheduled', 'interviews', $interview->id, null, [
                'application_id' => $app->id, 'scheduled_at' => $start->toDateTimeString(),
            ]);

            return $interview;
        });
    }

    public function reschedule(Interview $interview, array $data): Interview
    {
        return DB::transaction(function () use ($interview, $data) {
            $i = Interview::whereKey($interview->id)->lockForUpdate()->firstOrFail();

            $this->assertScheduled($i);

            [$start, $minutes] = $this->window($data);
            $this->assertFree($i->application, $data['interviewer_id'] ?? null, $start, $minutes, $i->id);

            $old = $i->only(['scheduled_at', 'duration_minutes', 'mode', 'location', 'interviewer_id']);

            $i->update([
                'scheduled_at' => $start,
                'duration_minutes' => $minutes,
                'mode' => $data['mode'],
                'location' => $this->clean($data['location'] ?? null),
                'interviewer_id' => ! empty($data['interviewer_id']) ? (int) $data['interviewer_id'] : null,
            ]);

            AuditLogger::log('rescheduled', 'interviews', $i->id, $old, $i->only(array_keys($old)));

            return $i->fresh();
        });
    }

    public function cancel(Interview $interview): Interview
    {
        return DB::transaction(function () use ($interview) {
            $i = Interview::whereKey($interview->id)->lockForUpdate()->firstOrFail();

            $this->assertScheduled($i);
            $i->update(['status' => 'cancelled']);
            AuditLogger::log('cancelled', 'interviews', $i->id, ['status' => 'scheduled'], ['status' => 'cancelled']);

            return $i->fresh();
        });
    }

    /** Clôture un entretien : compte rendu et note, ou « absent ». Réservé à l'évaluateur ou à qui a le droit de modification. */
    public function complete(Interview $interview, string $outcome, ?int $score = null, ?string $feedback = null): Interview
    {
        abort_unless(
            auth()->user()->canDo('update', 'interviews') || (int) $interview->interviewer_id === (int) auth()->id(),
            403
        );

        return DB::transaction(function () use ($interview, $outcome, $score, $feedback) {
            $i = Interview::whereKey($interview->id)->lockForUpdate()->firstOrFail();

            $this->assertScheduled($i);

            if (! in_array($outcome, ['done', 'no_show'], true)) {
                throw ValidationException::withMessages(['interview' => 'Résultat d’entretien inconnu.']);
            }
            if ($i->scheduled_at->isFuture()) {
                throw ValidationException::withMessages(['interview' => 'Cet entretien n’a pas encore eu lieu.']);
            }

            $feedback = $this->clean($feedback);

            if ($outcome === 'done') {
                if ($score === null || $score < 1 || $score > 5) {
                    throw ValidationException::withMessages(['interview' => 'Donnez une note de 1 à 5.']);
                }
                if (mb_strlen((string) $feedback) < 3) {
                    throw ValidationException::withMessages(['interview' => 'Rédigez un court compte rendu.']);
                }
            } else {
                $score = null;
            }

            $i->update(['status' => $outcome, 'score' => $score, 'feedback' => $feedback]);
            AuditLogger::log('completed', 'interviews', $i->id, ['status' => 'scheduled'], ['status' => $outcome, 'score' => $score]);

            return $i->fresh();
        });
    }

    // ---------- Internes ----------

    private function assertScheduled(Interview $i): void
    {
        if ($i->status !== 'scheduled') {
            throw ValidationException::withMessages(['interview' => 'Cet entretien n’est plus modifiable.']);
        }
    }

    /** @return array{0: Carbon, 1: int} */
    private function window(array $data): array
    {
        $start = Carbon::parse($data['scheduled_at'], config('app.timezone'));

        if ($start->isPast()) {
            throw ValidationException::withMessages(['interview' => 'L’entretien doit être planifié dans le futur.']);
        }

        return [$start, max(15, min(480, (int) ($data['duration_minutes'] ?? 60)))];
    }

    /** Ni l'évaluateur ni le candidat ne peuvent avoir deux entretiens qui se chevauchent. */
    private function assertFree(Application $app, $interviewerId, Carbon $start, int $minutes, ?int $ignoreId): void
    {
        $end = $start->copy()->addMinutes($minutes);

        $others = Interview::with('application:id,candidate_id')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<', $end)
            ->where('scheduled_at', '>', $start->copy()->subMinutes(480)) // durée maximale d'un entretien
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->lockForUpdate()
            ->get();

        foreach ($others as $other) {
            if ($other->endsAt()->lte($start)) {
                continue;
            }
            if ($interviewerId && (int) $other->interviewer_id === (int) $interviewerId) {
                throw ValidationException::withMessages(['interview' => 'Cet évaluateur a déjà un entretien sur ce créneau.']);
            }
            if ($other->application && $other->application->candidate_id === $app->candidate_id) {
                throw ValidationException::withMessages(['interview' => 'Ce candidat a déjà un entretien sur ce créneau.']);
            }
        }
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }
}
