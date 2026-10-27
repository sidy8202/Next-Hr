<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExplanationCase extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'awaiting_response' => 'En attente de réponse',
        'responded' => 'Réponse reçue',
        'decided' => 'Décision rendue',
        'withdrawn' => 'Retirée',
    ];

    protected $fillable = [
        'reference', 'employee_id', 'issued_by_user_id', 'signatory_name', 'signatory_title', 'subject', 'body', 'copies',
        'response_days', 'response_deadline', 'status', 'employee_viewed_at', 'responded_at', 'response_body',
        'response_attachment_path', 'response_attachment_name', 'decision_type', 'decision_subject', 'decision_body', 'decided_at',
        'decided_by_user_id', 'suspension_start', 'suspension_end', 'suspension_paid', 'dismissal_effective_date',
        'withdrawn_reason', 'reminder_sent_at', 'overdue_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'response_deadline' => 'date', 'employee_viewed_at' => 'datetime', 'responded_at' => 'datetime', 'decided_at' => 'datetime',
            'suspension_start' => 'date', 'suspension_end' => 'date', 'suspension_paid' => 'boolean', 'dismissal_effective_date' => 'date',
            'reminder_sent_at' => 'datetime', 'overdue_notified_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExplanationDocument::class, 'case_id')->orderBy('id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'awaiting_response' && $this->response_deadline->lt(today());
    }

    public function decisionLabel(): ?string
    {
        return $this->decision_type ? config("discipline.decisions.{$this->decision_type}.label") : null;
    }
}
