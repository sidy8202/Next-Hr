<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

/** Paramètres de congé d'UNE entreprise (lus par identifiant d'entreprise : utilisable sans utilisateur connecté). */
class LeaveSetting extends Model
{
    public const COUNT_MODES = [
        'calendar' => 'Jours calendaires (week-ends et jours fériés compris)',
        'calendar_no_holidays' => 'Jours calendaires, jours fériés exclus',
        'scheduled' => 'Jours de travail prévus au planning de l’employé (jours fériés exclus)',
    ];

    public const ACCRUAL_MODES = [
        'monthly' => 'Par mois de présence révolu',
        'per_worked_days' => 'Par tranche de jours réellement travaillés (rotations)',
    ];

    protected $fillable = [
        'negative_tolerance', 'default_monthly_days', 'workdays', 'deduct_unjustified', 'recovery_validity_days',
        'count_mode', 'accrual_mode', 'accrual_per_days', 'seniority_bonuses',
    ];

    protected function casts(): array
    {
        return [
            'negative_tolerance' => 'float',
            'default_monthly_days' => 'float',
            'workdays' => 'array',
            'deduct_unjustified' => 'boolean',
            'recovery_validity_days' => 'integer',
            'accrual_per_days' => 'integer',
            'seniority_bonuses' => 'array',
        ];
    }

    public static function for(int $companyId): self
    {
        $setting = static::where('company_id', $companyId)->first();

        if ($setting) {
            return $setting;
        }

        try {
            $setting = new static();
            $setting->forceFill([
                'company_id' => $companyId,
                'negative_tolerance' => config('leaves.negative_tolerance'),
                'default_monthly_days' => config('leaves.default_monthly_days'),
                'workdays' => config('leaves.workdays'),
                'deduct_unjustified' => config('leaves.deduct_unjustified'),
                'recovery_validity_days' => config('leaves.recovery_validity_days'),
            ])->save();

            return $setting->refresh(); // charge les valeurs par défaut de la base (mode de décompte, etc.)
        } catch (UniqueConstraintViolationException) {
            return static::where('company_id', $companyId)->firstOrFail();
        }
    }

    /** @return array<int, int> jours ouvrés (1 = lundi … 7 = dimanche) */
    public function workdayList(): array
    {
        return array_map('intval', $this->workdays ?: config('leaves.workdays'));
    }

    /** @return array<int, array{0: int, 1: float}> majorations d'ancienneté : [années de service, jours en plus par an] */
    public function bonusRules(): array
    {
        return $this->seniority_bonuses ?? config('leaves.seniority_bonuses', []);
    }
}
