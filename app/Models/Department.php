<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{

    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'head_id',
        'is_active',
    ];



    protected $casts = [
        'is_active' => 'boolean',
    ];


    /**
     * Le chef / responsable du département.
     */
     public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    /**
     * Les postes rattachés à ce département.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Les employés de ce département.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }


}
