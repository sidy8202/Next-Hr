<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayItem extends Model
{
    use BelongsToCompany;

    protected $fillable = ['employee_id', 'pay_item_id', 'amount', 'rate', 'start_date', 'end_date', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayItem::class, 'pay_item_id');
    }

    public function scopeInForceOn(Builder $q, string $date): Builder
    {
        return $q->whereDate('start_date', '<=', $date)->where(fn ($w) => $w->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
    }
}
