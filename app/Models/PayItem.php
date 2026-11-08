<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayItem extends Model
{
    use BelongsToCompany;

    public const TYPES = [
        'gain' => 'Gain',
        'gross_deduction' => 'Retenue sur le brut',
        'benefit_in_kind' => 'Avantage en nature',
        'deduction_internal' => 'Retenue interne',
        'deduction_individual' => 'Retenue individuelle',
    ];

    public const CALC_TYPES = [
        'system' => 'Fourni par le moteur',
        'fixed' => 'Montant fixe',
        'percent_base' => '% du salaire de base',
        'percent_gross' => '% du brut',
        'percent_item' => "% d'une autre rubrique",
        'qty_rate' => 'Quantité × taux',
        'scale' => 'Barème (ancienneté)',
    ];

    protected $fillable = [
        'code', 'label', 'type', 'system_key', 'is_system', 'subject_to_social', 'taxable', 'is_compulsory', 'calc_type',
        'default_amount', 'default_rate', 'ref_item_id', 'floor_amount', 'cap_amount', 'show_on_payslip', 'sort', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean', 'subject_to_social' => 'boolean', 'taxable' => 'boolean', 'is_compulsory' => 'boolean',
            'show_on_payslip' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    public function scaleRows(): HasMany
    {
        return $this->hasMany(PayItemScaleRow::class)->orderBy('from_years');
    }

    public function refItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ref_item_id');
    }

    /** Ce type de calcul attend un montant (fixe, ou taux unitaire par unité). */
    public static function usesAmount(string $calcType): bool
    {
        return in_array($calcType, ['fixed', 'qty_rate'], true);
    }

    /** Ce type de calcul attend un pourcentage. */
    public static function usesRate(string $calcType): bool
    {
        return in_array($calcType, ['percent_base', 'percent_gross', 'percent_item'], true);
    }
}
