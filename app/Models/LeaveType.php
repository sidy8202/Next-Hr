<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use BelongsToCompany;

    protected $fillable = ['code', 'name', 'pool', 'requires_document', 'max_days', 'paid', 'active', 'sort'];

    protected function casts(): array
    {
        return [
            'requires_document' => 'boolean',
            'max_days' => 'float',
            'paid' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /** Crée les types de congé par défaut d'une entreprise qui n'en a pas encore. */
    public static function ensureDefaults(int $companyId): void
    {
        if (static::withoutGlobalScopes()->where('company_id', $companyId)->exists()) {
            return;
        }

        foreach (config('leaves.default_types') as $i => $row) {
            $type = new static();
            $type->forceFill($row + ['company_id' => $companyId, 'active' => true, 'sort' => $i])->saveQuietly();
        }
    }

    public function poolLabel(): string
    {
        return match ($this->pool) {
            'annual' => 'Retire du solde de congé',
            'recovery' => 'Retire des jours de récupération',
            default => 'Ne retire aucun jour',
        };
    }
}
