<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PermissionType extends Model
{
    use BelongsToCompany;

    public const POLICIES = [
        'deduct_leave' => 'L’excédent est retiré du congé annuel',
        'refuse' => 'L’excédent est refusé',
        'unpaid' => 'L’excédent est un congé non payé',
    ];

    protected $fillable = [
        'code', 'name', 'kind', 'free_days', 'excess_policy', 'max_days', 'yearly_cap_days', 'requires_document',
        'hours_free_per_month', 'sheet_message', 'active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'free_days' => 'float', 'max_days' => 'float', 'yearly_cap_days' => 'float', 'hours_free_per_month' => 'float',
            'requires_document' => 'boolean', 'active' => 'boolean',
        ];
    }

    public static function ensureDefaults(int $companyId): void
    {
        if (static::withoutGlobalScopes()->where('company_id', $companyId)->exists()) {
            return;
        }

        foreach (config('permission_rules.default_types') as $i => $row) {
            $type = new static();
            $type->forceFill($row + ['company_id' => $companyId, 'active' => true, 'sort' => $i])->saveQuietly();
        }
    }

    public function summary(): string
    {
        if ($this->kind === 'hours') {
            return $this->hours_free_per_month !== null ? "{$this->hours_free_per_month} h gratuites par mois" : 'Heures sans limite';
        }

        $free = rtrim(rtrim(number_format($this->free_days, 1, ',', ''), '0'), ',') ?: '0';

        return "{$free} j gratuits par demande";
    }
}
