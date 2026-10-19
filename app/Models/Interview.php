<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use BelongsToCompany;

    public const MODES = ['onsite' => 'Sur site', 'phone' => 'Téléphone', 'video' => 'Visioconférence'];

    public const STATUSES = [
        'scheduled' => 'Planifié',
        'done' => 'Réalisé',
        'cancelled' => 'Annulé',
        'no_show' => 'Absent',
    ];

    protected $fillable = [
        'application_id', 'scheduled_at', 'duration_minutes', 'mode', 'location', 'interviewer_id',
        'status', 'feedback', 'score', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'score' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function endsAt(): CarbonInterface
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
