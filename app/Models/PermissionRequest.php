<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'employee_id', 'permission_type_id', 'start_date', 'end_date', 'start_time', 'end_time', 'days', 'hours', 'free_days',
        'deducted_days', 'unpaid_days', 'reason', 'exception_reason', 'exceeds_balance', 'balance_before', 'balance_after',
        'attachment_path', 'attachment_name', 'status', 'document_id', 'decided_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date', 'days' => 'float', 'hours' => 'float', 'free_days' => 'float',
            'deducted_days' => 'float', 'unpaid_days' => 'float', 'exceeds_balance' => 'boolean',
            'balance_before' => 'float', 'balance_after' => 'float', 'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PermissionType::class, 'permission_type_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approvable_id')->where('approvable_type', 'permission')->orderBy('position');
    }

    public function statusLabel(): string
    {
        return LeaveRequest::STATUSES[$this->status] ?? $this->status;
    }

    public function isHours(): bool
    {
        return $this->type?->kind === 'hours';
    }

    public function periodLabel(): string
    {
        if ($this->isHours()) {
            return 'le '.$this->start_date->format('d/m/Y').' de '.substr((string) $this->start_time, 0, 5).' à '.substr((string) $this->end_time, 0, 5);
        }

        return $this->start_date->equalTo($this->end_date)
            ? 'le '.$this->start_date->format('d/m/Y')
            : 'du '.$this->start_date->format('d/m/Y').' au '.$this->end_date->format('d/m/Y');
    }
}
