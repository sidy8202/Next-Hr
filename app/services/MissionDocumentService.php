<?php

namespace App\Services;

use App\Enums\MissionStatus;
use App\Mail\MissionOrderSignedMail;
use App\Models\Employee;
use App\Models\Letterhead;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionParticipant;
use App\Models\User;
use App\Notifications\MissionNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Ordres de mission individuels : génération, dépôt du scan signé, accès aux fichiers, notifications. */
class MissionDocumentService
{
    public function __construct(private MissionService $missions)
    {
    }

    /* ───────────── Accès ───────────── */

    /**
     * - ordre à signer (généré) : RH et DG seulement ;
     * - ordre signé : RH, DG, le participant, le demandeur et le chef du participant (version courante pour eux).
     * L'ordre ne contient aucun montant : le demandeur peut donc recevoir tous les ordres de sa mission.
     */
    public function canAccess(User $user, Mission $mission, MissionDocument $doc): bool
    {
        if ((int) $doc->mission_id !== (int) $mission->id || (int) $mission->company_id !== (int) $user->company_id) {
            return false;
        }

        if ($this->missions->isHr($user) || $this->missions->isDg($user)) {
            return true;
        }

        if ($doc->kind !== 'signed' || ! $doc->isCurrent()) {
            return false;
        }

        $p = $doc->participant()->with('employee:id,manager_id')->first();

        if ((int) $mission->requested_by === $user->id) {
            return true;
        }

        if (! $user->employee_id || ! $p) {
            return false;
        }

        return (int) $p->employee_id === (int) $user->employee_id
            || (int) $p->employee?->manager_id === (int) $user->employee_id;
    }

    /* ───────────── Génération ───────────── */

    /** Crée l'ordre de chaque participant qui n'en a pas (ou régénère si $force, sauf ceux déjà signés). */
    public function generateOrders(Mission $mission, bool $force = false): int
    {
        $mission->load(['participants.employee.position', 'participants.department', 'approvals.user']);

        $letterhead = Letterhead::for($mission->company_id);
        $signatories = $this->signatories($mission);
        $date = $mission->approvals->where('step', 'dg')->where('decision', 'approved')->sortBy('id')->last()?->decided_at ?? now();
        $created = 0;

        foreach ($mission->participants->sortBy('id')->values() as $i => $p) {
            $current = MissionDocument::where('participant_id', $p->id)->where('kind', 'order')->whereNull('superseded_at')->first();

            if ($current && ! $force) {
                continue;
            }

            // Un ordre déjà signé n'est jamais remplacé.
            if (MissionDocument::where('participant_id', $p->id)->where('kind', 'signed')->whereNull('superseded_at')->exists()) {
                continue;
            }

            $reference = $mission->reference.'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            [$content, $ext] = app(MissionOrderRenderer::class)->render($mission, $p, $reference, $letterhead, $signatories, $date);

            $version = ($current?->version ?? 0) + 1;
            $path = "companies/{$mission->company_id}/missions/{$mission->id}/ordre-{$reference}-v{$version}.{$ext}";
            Storage::disk('local')->put($path, $content);

            DB::transaction(function () use ($current, $mission, $p, $version, $reference, $path, $content) {
                $current?->update(['superseded_at' => now()]);

                MissionDocument::create([
                    'mission_id' => $mission->id, 'participant_id' => $p->id, 'kind' => 'order', 'version' => $version,
                    'reference' => $reference, 'file_path' => $path, 'size' => strlen($content), 'sha256' => hash('sha256', $content),
                    'created_by' => auth()->id(),
                ]);
            });

            $created++;
        }

        if ($created) {
            AuditLogger::log('orders_generated', 'mission_orders', $mission->id, null, ['count' => $created]);
        }

        return $created;
    }

    /** Action RH : (re)génère les ordres manquants. */
    public function regenerate(Mission $mission, User $user): int
    {
        abort_unless($this->missions->isHr($user) && (int) $mission->company_id === (int) $user->company_id && $mission->status === MissionStatus::Approved, 403);

        return $this->generateOrders($mission);
    }

    private function signatories(Mission $mission): array
    {
        $by = fn (string $step) => $mission->approvals->where('step', $step)->where('decision', 'approved')->sortBy('id')->last()?->user;

        return [
            ['label' => 'Le Responsable des Ressources Humaines'] + $this->person($by('hr')),
            ['label' => 'La Direction'] + $this->person($by('dg')),
        ];
    }

    private function person(?User $user): array
    {
        $emp = $user?->employee_id ? Employee::withoutGlobalScopes()->with('position:id,title')->find($user->employee_id) : null;

        return ['name' => $emp?->full_name ?? $user?->name ?? '', 'title' => (string) ($emp?->position?->title ?? '')];
    }

    /* ───────────── Dépôt du scan signé ───────────── */

    public function uploadSigned(Mission $mission, MissionParticipant $participant, UploadedFile $file, User $user): MissionDocument
    {
        abort_unless($this->missions->isHr($user) && (int) $mission->company_id === (int) $user->company_id, 403);
        abort_unless($mission->status === MissionStatus::Approved && (int) $participant->mission_id === (int) $mission->id, 403);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $allowed = explode(',', config('missions.signed.mimes'));

        if (! in_array($ext, $allowed, true) || $file->getSize() > config('missions.signed.max_kb') * 1024) {
            throw ValidationException::withMessages(['mission' => 'Format ou taille du fichier non accepté (PDF, JPG ou PNG, '.(config('missions.signed.max_kb') / 1024).' Mo maximum).']);
        }

        $order = MissionDocument::where('participant_id', $participant->id)->where('kind', 'order')->whereNull('superseded_at')->first();

        if (! $order) {
            throw ValidationException::withMessages(['mission' => 'Générez d’abord l’ordre de ce participant.']);
        }

        $previous = MissionDocument::where('participant_id', $participant->id)->where('kind', 'signed')->whereNull('superseded_at')->first();
        $version = ($previous?->version ?? 0) + 1;

        $path = $file->storeAs("companies/{$mission->company_id}/missions/{$mission->id}", "signe-{$order->reference}-v{$version}-".Str::uuid().'.'.$ext, 'local');

        $doc = DB::transaction(function () use ($previous, $mission, $participant, $order, $version, $path, $file, $user) {
            $previous?->update(['superseded_at' => now()]);

            return MissionDocument::create([
                'mission_id' => $mission->id, 'participant_id' => $participant->id, 'kind' => 'signed', 'version' => $version,
                'reference' => $order->reference, 'file_path' => $path, 'original_name' => $file->getClientOriginalName(),
                'size' => Storage::disk('local')->size($path), 'sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
                'created_by' => $user->id,
            ]);
        });

        AuditLogger::log('signed_uploaded', 'mission_orders', $mission->id, null, ['participant_id' => $participant->id, 'version' => $version]);
        $this->notifySigned($mission, $participant, $doc, $user);

        return $doc;
    }

    private function notifySigned(Mission $mission, MissionParticipant $participant, MissionDocument $doc, User $uploader): void
    {
        $participant->loadMissing('employee:id,first_name,last_name,manager_id');
        $name = $participant->employee->full_name;
        $company = Letterhead::for($mission->company_id)->legal_name ?: config('app.name');

        $self = User::where('employee_id', $participant->employee_id)->get();
        $others = User::whereKey($mission->requested_by)->get()
            ->merge($participant->employee->manager_id ? User::where('employee_id', $participant->employee->manager_id)->get() : collect())
            ->reject(fn (User $u) => $u->id === $uploader->id || $self->contains('id', $u->id))
            ->unique('id');

        foreach ([[$self, true], [$others, false]] as [$users, $forSelf]) {
            if ($users->isEmpty()) {
                continue;
            }

            Notification::send($users, new MissionNotification(
                $mission->id,
                $forSelf ? 'Votre ordre de mission signé est disponible' : 'Ordre de mission signé disponible',
                $forSelf ? "Votre ordre de mission {$doc->reference} ({$mission->destination_zone}) est signé." : "L’ordre de mission signé de {$name} ({$doc->reference}) est disponible.",
            ));

            foreach ($users as $u) {
                if (! $u->email) {
                    continue;
                }

                try {
                    Mail::to($u->email)->send(new MissionOrderSignedMail($mission, $doc, $company, $name, $forSelf));
                } catch (Throwable $e) {
                    report($e); // la notification interne reste envoyée
                }
            }
        }
    }
}
