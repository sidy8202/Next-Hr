<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionDocument extends Model
{
    protected $fillable = [
        'mission_id', 'participant_id', 'kind', 'version', 'reference', 'file_path',
        'original_name', 'size', 'sha256', 'created_by', 'superseded_at', 'voided_at',
    ];

    protected function casts(): array
    {
        return ['superseded_at' => 'datetime', 'voided_at' => 'datetime'];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function participant(): BelongsTo { return $this->belongsTo(MissionParticipant::class, 'participant_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }
}
