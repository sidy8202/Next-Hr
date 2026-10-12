<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'birth_place',
        'nationality',
        'is_expatriate',
        'marital_status',
        'national_id',
        'work_permit_number',
        'work_permit_expires_at',
        'phone',
        'personal_email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'department_id',
        'position_id',
        'manager_id',
        'hire_date',
        'status',
        'photo_path',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'work_permit_expires_at' => 'date',
        'hire_date' => 'date',
        'is_expatriate' => 'boolean',
    ];

    /**
     * Le département de l'employé.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Le poste de l'employé.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Le manager / responsable direct de l'employé.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Les subordonnés directs gérés par cet employé.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Le compte utilisateur rattaché à cet employé.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Les contrats de l'employé.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
