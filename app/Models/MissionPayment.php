<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionPayment extends Model
{
    protected $fillable = [
        'mission_id', 'participant_id', 'sequence', 'label', 'planned_amount', 'planned_date', 'status',
        'paid_amount', 'paid_at', 'method', 'reference', 'note', 'paid_by',
        'voided_at', 'void_reason', 'voided_by', 'receipt_path', 'receipt_name', 'receipt_sha256',
    ];

    protected function casts(): array
    {
        return ['planned_date' => 'date', 'paid_at' => 'date', 'voided_at' => 'datetime'];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function participant(): BelongsTo { return $this->belongsTo(MissionParticipant::class, 'participant_id'); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }

    public function isOverdue(): bool
    {
        return $this->status === 'planned' && $this->planned_date !== null && $this->planned_date->lt(today());
    }

    public function receiptExtension(): string
    {
        return strtolower(pathinfo((string) $this->receipt_path, PATHINFO_EXTENSION));
    }
}
