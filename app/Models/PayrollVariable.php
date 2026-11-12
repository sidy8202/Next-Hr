<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Services\Payroll\PayrollPeriodGuard;
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
        'assignment' => "Prime d'affectation",
    ];

    public const STATUSES = ['proposed' => 'Proposée', 'confirmed' => 'Confirmée', 'ignored' => 'Ignorée'];

    protected $fillable = [
        'employee_id', 'period', 'kind', 'pay_item_id', 'quantity', 'unit', 'rate_percent', 'amount_override', 'days_mode',
        'source_type', 'source_id', 'source_ref', 'status', 'note', 'created_by', 'confirmed_by', 'confirmed_at', 'is_edited',
    ];

    protected static function booted(): void
    {
        // Une paie clôturée est définitive : ses variables ne se modifient ni ne se suppriment plus.
        static::saving(fn (self $v) => PayrollPeriodGuard::assertOpen($v->period));
        static::deleting(fn (self $v) => PayrollPeriodGuard::assertOpen($v->period));
    }

    protected function casts(): array
    {
        return ['quantity' => 'float', 'rate_percent' => 'float', 'confirmed_at' => 'datetime', 'is_edited' => 'boolean'];
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
