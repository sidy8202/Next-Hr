<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\MatriculeSequence;
use App\Models\MatriculeSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Génère le matricule selon le gabarit choisi par la RH.
 * À APPELER DANS UNE TRANSACTION : le verrou sur le compteur dure jusqu'au commit ou au rollback,
 * donc deux saisies simultanées ne reçoivent jamais le même numéro, et un rollback ne laisse aucun trou.
 *
 * Variables : {PREFIX} {SEQ:4} {YYYY} {YY} {MM} {DEPT} {TYPE} {PRENOM} {NOM}
 */
class MatriculeGenerator
{
    public function next(Department $department, ?CarbonInterface $hireDate = null, string $firstName = '', string $lastName = '', string $contractType = ''): string
    {
        $hireDate ??= now();

        $setting = MatriculeSetting::where('is_active', true)->first()
            ?? MatriculeSetting::forceCreate([
                'company_id' => $department->company_id,
                'pattern' => '{PREFIX}{SEQ:4}',
                'prefix' => 'EM',
                'counter_scope' => 'global',
                'is_active' => true,
            ]);

        $scopeKey = match ($setting->counter_scope) {
            'year' => (string) $hireDate->year,
            'department' => (string) $department->code,
            default => 'global',
        };

        $sequence = MatriculeSequence::where('matricule_setting_id', $setting->id)
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->first()
            ?? MatriculeSequence::forceCreate([
                'company_id' => $setting->company_id,
                'matricule_setting_id' => $setting->id,
                'scope_key' => $scopeKey,
                'last_number' => 0,
            ]);

        $hasSeq = str_contains($setting->pattern, '{SEQ');
        $attempts = 0;

        do {
            $sequence->increment('last_number');

            $matricule = $this->render($setting->pattern, (string) $setting->prefix, (int) $sequence->last_number, $department, $hireDate, $firstName, $lastName, $contractType);

            // On saute les numéros déjà pris (employés créés à la main, importés ou supprimés).
            $taken = $hasSeq && Employee::withTrashed()->where('matricule', $matricule)->exists();
        } while ($taken && ++$attempts < 500);

        // Sans compteur dans le gabarit (ex. {PRENOM}.{NOM}) : on gère les homonymes.
        return $hasSeq ? $matricule : $this->ensureUnique($matricule);
    }

    private function render(string $pattern, string $prefix, int $seq, Department $department, CarbonInterface $date, string $first, string $last, string $type): string
    {
        $out = preg_replace_callback(
            '/\{SEQ(?::(\d+))?\}/',
            fn (array $m) => str_pad((string) $seq, (int) ($m[1] ?? 0), '0', STR_PAD_LEFT),
            $pattern
        );

        return strtr($out, [
            '{PREFIX}' => $prefix,
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DEPT}' => (string) $department->code,
            '{TYPE}' => $type,
            '{PRENOM}' => Str::slug($first, ''),
            '{NOM}' => Str::slug($last, ''),
        ]);
    }

    private function ensureUnique(string $candidate): string
    {
        $matricule = $candidate;
        $i = 2;

        while (Employee::withTrashed()->where('matricule', $matricule)->exists()) {
            $matricule = $candidate.$i++;
        }

        return $matricule;
    }
}
