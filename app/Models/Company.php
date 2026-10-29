<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
     use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'country',
        'timezone',
        'locale',
        'currency',
        'logo_path',
        'status',
        'trial_ends_at',
    ];

    /**
     * Les utilisateurs rattachés à cette entreprise.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Les employés de cette entreprise.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Les départements de cette entreprise.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
