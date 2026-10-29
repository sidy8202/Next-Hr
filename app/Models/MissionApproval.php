<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionApproval extends Model
{
    protected $fillable = ['mission_id', 'user_id', 'step', 'decision', 'comment', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
