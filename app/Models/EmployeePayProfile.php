<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayProfile extends Model
{
    use BelongsToCompany;

    protected $fillable = ['employee_id', 'pay_profile_id', 'effective_from', 'effective_until', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PayProfile::class, 'pay_profile_id');
    }

    public function scopeInForceOn(Builder $q, string $date): Builder
    {
        return $q->whereDate('effective_from', '<=', $date)->where(fn ($w) => $w->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date));
    }
}
