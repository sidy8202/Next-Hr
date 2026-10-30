<?php

namespace App\Models;

use App\Enums\MissionStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Department;

class Mission extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'reference', 'requested_by', 'department_id', 'purpose',
        'destination_zone', 'lodging', 'start_date', 'end_date', 'program',
        'status', 'submitted_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => MissionStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function participants(): HasMany { return $this->hasMany(MissionParticipant::class); }
    public function approvals(): HasMany { return $this->hasMany(MissionApproval::class); }
    public function documents(): HasMany { return $this->hasMany(MissionDocument::class); }
    public function payments(): HasMany { return $this->hasMany(MissionPayment::class); }

    /** Missions approuvées couvrant la date donnée (pour le statut « en mission »). */
    public function scopeOngoingOn(Builder $query, $date): Builder
    {
        return $query
            ->where('status', MissionStatus::Approved)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }
}
