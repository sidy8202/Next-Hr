<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use BelongsToCompany;

    /**
     * Protection contre le mass-assignment :
     * 'id' et 'company_id' sont verrouillés, tous les autres champs sont autorisés.
     */
    protected $guarded = ['id', 'company_id'];

    /**
     * Le département dont dépend ce poste.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Les employés occupant ce poste.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
