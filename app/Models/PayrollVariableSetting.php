<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollVariableSetting extends Model
{
    protected $fillable = ['pay_basis', 'suspension_count_mode', 'rest_weekdays', 'unjustified_absence_unpaid', 'loan_effort_alert_percent'];

    protected function casts(): array
    {
        return ['rest_weekdays' => 'array', 'unjustified_absence_unpaid' => 'boolean'];
    }

    /** Réglages de l'entreprise ; créés avec leurs valeurs de départ (à confirmer) si absents. */
    public static function forCompany(int $companyId): self
    {
        $s = static::where('company_id', $companyId)->first();

        if (! $s) {
            $s = new static();
            $s->forceFill(['company_id' => $companyId, 'rest_weekdays' => [0]])->save();
            $s->refresh();
        }

        return $s;
    }

    /** Jours non ouvrables (0 = dimanche), dimanche par défaut. */
    public function restDays(): array
    {
        return array_map('intval', $this->rest_weekdays ?? [0]);
    }
}
