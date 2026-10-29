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

    /**
     * Crée les types de congé par défaut qui manquent (repérés par leur code), même si d'anciens types
     * de la première version de l'application existent déjà dans la table.
     */
    public static function ensureDefaults(int $companyId): void
    {
        $existing = static::withoutGlobalScopes()->where('company_id', $companyId)->pluck('code')->filter()->all();

        foreach (config('leaves.default_types') as $i => $row) {
            if (in_array($row['code'], $existing, true)) {
                continue;
            }

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
