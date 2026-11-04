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
        'sector',
        'suspended_at',
        'suspension_reason',
        'trial_ends_at',
    ];
    protected $casts = [
        'suspended_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Les utilisateurs rattachés à cette entreprise.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
