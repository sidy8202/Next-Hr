<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Validée',
        'rejected' => 'Refusée',
        'cancelled' => 'Annulée',
    ];

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'start_half', 'end_half', 'days', 'reason',
        'exception_reason', 'exceeds_balance', 'balance_before', 'balance_after', 'interim_employee_id',
        'attachment_path', 'attachment_name', 'status', 'document_id', 'decided_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_half' => 'boolean',
            'end_half' => 'boolean',
            'days' => 'float',
            'exceeds_balance' => 'boolean',
            'balance_before' => 'float',
            'balance_after' => 'float',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function interim(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'interim_employee_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approvable_id')->where('approvable_type', 'leave')->orderBy('position');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** « du 12/11/2026 au 20/11/2026 » (ou « le 12/11/2026 » pour un jour). */
    public function periodLabel(): string
    {
        return $this->start_date->equalTo($this->end_date)
            ? 'le '.$this->start_date->format('d/m/Y')
            : 'du '.$this->start_date->format('d/m/Y').' au '.$this->end_date->format('d/m/Y');
    }
}
