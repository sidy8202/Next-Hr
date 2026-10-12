<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'department_id',
        'title',
        'grade',
        'salary_min',
        'salary_max',
    ];

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
