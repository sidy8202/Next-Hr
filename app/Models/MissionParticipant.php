<?php

namespace App\Models;

use App\Enums\MissionRole;
use App\Enums\PerdiemMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionParticipant extends Model
{
    protected $fillable = [
        'mission_id', 'employee_id', 'department_id', 'role', 'perdiem_mode',
        'days', 'daily_rate', 'total_amount', 'salary_divisor', 'rate_locked_at',
        'payment_mode', 'payment_note',
    ];

    protected function casts(): array
    {
        return [
            'role' => MissionRole::class,
            'perdiem_mode' => PerdiemMode::class,
            'rate_locked_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(MissionDocument::class, 'participant_id'); }

    public function isRateLocked(): bool
    {
        return $this->rate_locked_at !== null;
    }
}
