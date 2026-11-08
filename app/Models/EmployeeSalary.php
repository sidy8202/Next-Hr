<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'employee_id', 'salary_grade_id', 'salary_step_id', 'mode', 'base_amount', 'supplement_amount', 'target_net',
        'effective_from', 'effective_until', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function grade(): BelongsTo { return $this->belongsTo(SalaryGrade::class, 'salary_grade_id'); }
    public function step(): BelongsTo { return $this->belongsTo(SalaryStep::class, 'salary_step_id'); }

    /** Salaire de base + sursalaire (hors indemnités et primes, qui viennent avec les rubriques de paie). */
    public function grossBase(): int
    {
        return (int) $this->base_amount + (int) $this->supplement_amount;
    }

    public function scopeInForceOn(Builder $q, string $date): Builder
    {
        return $q->whereDate('effective_from', '<=', $date)->where(fn ($w) => $w->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date));
    }
}
