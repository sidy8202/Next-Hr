<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Banque de CV : les composants Livewire VALIDENT, ce service ENREGISTRE (transaction, fichiers privés).
 */
class CandidateService
{
    private const FIELDS = [
        'first_name', 'last_name', 'email', 'phone', 'city', 'education_level', 'last_degree',
        'degree_field', 'school', 'graduation_year', 'experience_years', 'current_job_title',
        'profile_category', 'source', 'notes',
    ];

    public function create(array $data, ?UploadedFile $cv = null): Candidate
    {
        $stored = null;

        try {
            return DB::transaction(function () use ($data, $cv, &$stored) {
                $attrs = $this->attributes($data) + [
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'consent_given_at' => now(),
                    'retention_until' => now()->addMonths((int) config('recruitment.retention_months'))->toDateString(),
                ];

                if ($cv) {
                    [$stored, $name] = $this->storeCv($cv);
                    $attrs['cv_path'] = $stored;
                    $attrs['cv_original_name'] = $name;
                }

                $candidate = Candidate::create($attrs);

                AuditLogger::log('created', 'candidates', $candidate->id, null, ['name' => $candidate->full_name]);

                return $candidate;
            });
        } catch (Throwable $e) {
            $this->deleteFile($stored); // pas de fichier orphelin si tout est annulé

            throw $e;
        }
    }

    public function update(Candidate $candidate, array $data, ?UploadedFile $cv = null, bool $removeCv = false): Candidate
    {
        $newPath = null;
        $oldPath = $candidate->cv_path;

        try {
            DB::transaction(function () use ($candidate, $data, $cv, $removeCv, &$newPath) {
                $attrs = $this->attributes($data);

                if ($cv) {
                    [$newPath, $name] = $this->storeCv($cv);
                    $attrs['cv_path'] = $newPath;
                    $attrs['cv_original_name'] = $name;
                } elseif ($removeCv) {
                    $attrs['cv_path'] = null;
                    $attrs['cv_original_name'] = null;
                }

                $old = $candidate->only(array_keys($attrs));
                $candidate->fill($attrs);
                $dirty = $candidate->getDirty();
                $candidate->save();

                if ($dirty) {
                    AuditLogger::log('updated', 'candidates', $candidate->id, array_intersect_key($old, $dirty), $dirty);
                }
            });
        } catch (Throwable $e) {
            $this->deleteFile($newPath);

            throw $e;
        }

        // L'ancien fichier n'est supprimé qu'une fois la transaction validée.
        if ($oldPath && $oldPath !== $candidate->cv_path) {
            $this->deleteFile($oldPath);
        }

        return $candidate;
    }

    public function setStatus(Candidate $candidate, string $status): Candidate
    {
        if (! in_array($status, ['active', 'archived'], true) || $candidate->status === 'hired') {
            throw ValidationException::withMessages(['candidate' => 'Ce changement de statut n’est pas possible.']);
        }

        $old = $candidate->status;
        $candidate->update(['status' => $status]);
        AuditLogger::log('updated', 'candidates', $candidate->id, ['status' => $old], ['status' => $status]);

        return $candidate;
    }

    public function delete(Candidate $candidate): void
    {
        if ($candidate->applications()->exists()) {
            throw ValidationException::withMessages([
                'candidate' => 'Ce profil a des candidatures : archivez-le plutôt que de le supprimer.',
            ]);
        }

        $path = $candidate->cv_path;

        DB::transaction(function () use ($candidate) {
            $candidate->delete();
            AuditLogger::log('deleted', 'candidates', $candidate->id, ['name' => $candidate->full_name], null);
        });

        $this->deleteFile($path);
    }

    // ---------- Internes ----------

    private function attributes(array $d): array
    {
        $out = [];

        foreach (self::FIELDS as $field) {
            $v = $d[$field] ?? null;
            $out[$field] = is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v;
        }

        $out['first_name'] = (string) $out['first_name'];
        $out['last_name'] = (string) $out['last_name'];
        $out['email'] = $out['email'] ? mb_strtolower($out['email']) : null;
        $out['education_level'] = $out['education_level'] ?: 'none';
        $out['experience_years'] = round(max(0, (float) ($out['experience_years'] ?? 0)), 1);
        $out['graduation_year'] = $out['graduation_year'] !== null ? (int) $out['graduation_year'] : null;
        $out['skills'] = $this->skills($d['skills'] ?? []);

        return $out;
    }

    /** « Excel, Réseaux; SQL » → ['excel', 'réseaux', 'sql'] (minuscules, sans doublon). */
    private function skills(array|string|null $raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[,;\n]+/', (string) $raw);

        return collect($items)
            ->map(fn ($s) => mb_strtolower(trim((string) $s)))
            ->filter()->unique()->take(30)->values()->all();
    }

    /** Range le CV dans le dossier PRIVÉ (storage/app/private). @return array{0:string,1:string} */
    private function storeCv(UploadedFile $file): array
    {
        $path = $file->storeAs(
            'companies/'.auth()->user()->company_id.'/candidates',
            Str::uuid().'.'.$file->extension(),
            'local'
        );

        return [$path, Str::limit($file->getClientOriginalName(), 150, '')];
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
