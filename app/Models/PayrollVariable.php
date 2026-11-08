<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollVariable extends Model
{
    use BelongsToCompany;

    public const KINDS = [
        'overtime_hours' => 'Heures supplémentaires',
        'worked_days' => 'Jours travaillés payés',
        'unpaid_absence' => 'Absence non rémunérée',
        'suspension' => 'Mise à pied',
        'bonus' => 'Prime ou gain ponctuel',
        'deduction' => 'Retenue ponctuelle',
    ];

    public const STATUSES = ['proposed' => 'Proposée', 'confirmed' => 'Confirmée', 'ignored' => 'Ignorée'];

    protected $fillable = [
        'employee_id', 'period', 'kind', 'pay_item_id', 'quantity', 'unit', 'rate_percent', 'amount_override', 'days_mode',
        'source_type', 'source_id', 'source_ref', 'status', 'note', 'created_by', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'rate_percent' => 'float', 'confirmed_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayItem::class, 'pay_item_id');
    }

    public function isAuto(): bool
    {
        return $this->source_type !== null;
    }
}
