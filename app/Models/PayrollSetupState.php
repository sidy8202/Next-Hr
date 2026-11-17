<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * État de l'assistant « Démarrer la paie » (une ligne par entreprise).
 * Comme PayrollSetting, ce modèle n'utilise pas le scope global : on filtre toujours explicitement par company_id.
 */
class PayrollSetupState extends Model
{
    /** Étapes qui se confirment à la main (les valeurs existent déjà avec des valeurs de départ). */
    public const CONFIRMABLE = ['settings', 'variable_settings'];

    protected $fillable = ['is_hidden', 'confirmed', 'skipped', 'welcomed_at', 'simulation_done_at'];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'confirmed' => 'array',
            'skipped' => 'array',
            'welcomed_at' => 'datetime',
            'simulation_done_at' => 'datetime',
        ];
    }

    public static function forCompany(int $companyId): self
    {
        $s = static::where('company_id', $companyId)->first();

        if (! $s) {
            $s = new static();
            $s->company_id = $companyId;
            $s->save();
            $s->refresh();
        }

        return $s;
    }

    /** Lecture sans création de ligne (menu, pastille) : null si l'entreprise n'a encore rien fait. */
    public static function peek(int $companyId): ?self
    {
        return static::where('company_id', $companyId)->first();
    }

    /** @return array<int, string> */
    public function confirmedKeys(): array
    {
        return array_values(array_map('strval', $this->confirmed ?? []));
    }

    /** @return array<int, string> */
    public function skippedKeys(): array
    {
        return array_values(array_map('strval', $this->skipped ?? []));
    }

    public function confirm(string $key): void
    {
        $keys = $this->confirmedKeys();

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->update(['confirmed' => $keys]);
        }
    }

    public function skip(string $key): void
    {
        $keys = $this->skippedKeys();

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->update(['skipped' => $keys]);
        }
    }

    public function unskip(string $key): void
    {
        $this->update(['skipped' => array_values(array_diff($this->skippedKeys(), [$key]))]);
    }

    /** Enregistre la première simulation de contrôle (ne change rien si déjà enregistrée). */
    public static function markSimulationDone(int $companyId): void
    {
        $s = static::forCompany($companyId);

        if (! $s->simulation_done_at) {
            $s->update(['simulation_done_at' => now()]);
        }
    }
}
