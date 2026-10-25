<?php

namespace App\Services;

use App\Models\JobOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobOfferService
{
    /** Passages de statut autorisés (« filled » est posé automatiquement quand tous les postes sont pourvus). */
    private const TRANSITIONS = [
        'draft'  => ['open'],
        'open'   => ['closed'],
        'closed' => ['open'],
        'filled' => ['closed'],
    ];

    public function create(array $data): JobOffer
    {
        return DB::transaction(function () use ($data) {
            $offer = JobOffer::create($this->attributes($data) + [
                'reference' => $this->nextReference(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            AuditLogger::log('created', 'job_offers', $offer->id, null, ['reference' => $offer->reference, 'title' => $offer->title]);

            return $offer;
        });
    }

    public function update(JobOffer $offer, array $data): JobOffer
    {
        return DB::transaction(function () use ($offer, $data) {
            $attrs = $this->attributes($data);
            $old = $offer->only(array_keys($attrs));
            $offer->fill($attrs);
            $dirty = $offer->getDirty();
            $offer->save();

            if ($dirty) {
                AuditLogger::log('updated', 'job_offers', $offer->id, array_intersect_key($old, $dirty), $dirty);
            }

            return $offer;
        });
    }

    public function setStatus(JobOffer $offer, string $to): JobOffer
    {
        if (! in_array($to, self::TRANSITIONS[$offer->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Ce changement de statut n’est pas possible.']);
        }

        if ($to === 'open') {
            $hired = $offer->applications()->where('stage', 'hired')->count();

            if ($hired >= $offer->vacancies) {
                throw ValidationException::withMessages([
                    'status' => 'Tous les postes sont pourvus : augmentez le nombre de postes avant de rouvrir l’offre.',
                ]);
            }
        }

        $from = $offer->status;
        $offer->forceFill([
            'status' => $to,
            'published_at' => $offer->published_at ?? ($to === 'open' ? now() : null),
        ])->save();

        AuditLogger::log('status_changed', 'job_offers', $offer->id, ['status' => $from], ['status' => $to]);

        return $offer;
    }

    public function delete(JobOffer $offer): void
    {
        if ($offer->applications()->exists()) {
            throw ValidationException::withMessages([
                'offer' => 'Cette offre a des candidatures : clôturez-la plutôt que de la supprimer.',
            ]);
        }

        DB::transaction(function () use ($offer) {
            $offer->delete();
            AuditLogger::log('deleted', 'job_offers', $offer->id, ['reference' => $offer->reference], null);
        });
    }

    // ---------- Internes ----------

    private function attributes(array $d): array
    {
        $none = fn ($v) => (is_string($v) ? trim($v) : $v) === '' ? null : (is_string($v) ? trim($v) : $v);

        return [
            'title' => trim((string) ($d['title'] ?? '')),
            'department_id' => $none($d['department_id'] ?? null) ? (int) $d['department_id'] : null,
            'position_id' => $none($d['position_id'] ?? null) ? (int) $d['position_id'] : null,
            'profile_category' => $none($d['profile_category'] ?? null),
            'contract_type' => $d['contract_type'] ?? 'CDI',
            'description' => $none($d['description'] ?? null),
            'requirements' => $none($d['requirements'] ?? null),
            'min_education_level' => $none($d['min_education_level'] ?? null),
            'min_experience_years' => round(max(0, (float) ($d['min_experience_years'] ?? 0)), 1),
            'vacancies' => max(1, (int) ($d['vacancies'] ?? 1)),
            'closes_at' => $none($d['closes_at'] ?? null),
            'required_skills' => $this->skills($d['required_skills'] ?? ''),
            'is_public' => (bool) ($d['is_public'] ?? false),
        ];
    }

    /** « Excel, Réseaux ; SQL » → ['excel', 'réseaux', 'sql'] */
    private function skills(array|string|null $raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[,;\n]+/', (string) $raw);

        return collect($items)->map(fn ($s) => mb_strtolower(trim((string) $s)))->filter()->unique()->take(30)->values()->all();
    }

    /** OFF-2026-001, OFF-2026-002… par entreprise (la contrainte d'unicité protège d'un doublon). */
    private function nextReference(): string
    {
        $year = now()->year;
        $n = JobOffer::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('OFF-%d-%03d', $year, $n++);
        } while (JobOffer::where('reference', $reference)->exists());

        return $reference;
    }
}
