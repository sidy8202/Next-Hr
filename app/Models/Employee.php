<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
class Employee extends Model
{
    use BelongsToCompany, SoftDeletes;

     /**
     * Protection contre le mass-assignment :
     * 'id' et 'company_id' sont verrouillés, tous les autres champs sont autorisés.
     */
    protected $guarded = ['id', 'company_id'];

    /**
     * Scope : Ne montre que les employés accessibles selon la portée de l'utilisateur.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->permissionScope('employees')) {
            'all'   => $query,
            'team'  => $query->where(fn ($q) => $q->whereKey($user->employee_id)->orWhere('manager_id', $user->employee_id)),
            'own'   => $query->whereKey($user->employee_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

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
        'spouse_works',
        'children_count',
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
        'children_count' => 'integer',
        'spouse_works'   => 'boolean',
        'birth_date' => 'date',
        'work_permit_expires_at' => 'date',
        'hire_date' => 'date',
        'is_expatriate' => 'boolean',
    ];

    /**
     * Accesseur : Nom complet (Prénom Nom).
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

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
    public function termination(): HasOne
    {
        return $this->hasOne(Termination::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function absencePermissions(): HasMany
    {
        return $this->hasMany(AbsencePermission::class);
    }

    /**
     * Participations de l'employé aux ordres de mission
     * (remplace l'ancienne relation missionOrderEmployees / MissionOrderEmployee).
     */
    public function missionParticipations(): HasMany
    {
        return $this->hasMany(MissionParticipant::class);
    }
}
