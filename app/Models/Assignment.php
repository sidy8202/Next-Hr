<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Poste occupé par un employé en plus (ou à la place) de son poste principal : intérim, détachement dans un autre
 * département ou une autre base, ou cumul de deux postes.
 */
class Assignment extends Model
{
    use BelongsToCompany;

    public const TYPES = [
        'interim' => 'Intérim',
        'secondment' => 'Détachement / mission sur un autre poste',
        'additional' => 'Poste cumulé',
    ];

    protected $fillable = [
        'employee_id', 'type', 'department_id', 'position_id', 'work_site_id', 'reports_to_employee_id', 'start_date', 'end_date',
        'allocation_percent', 'allowance_amount', 'allowance_basis', 'acts_as_head', 'manager_validates', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date', 'allocation_percent' => 'integer', 'allowance_amount' => 'float',
            'acts_as_head' => 'boolean', 'manager_validates' => 'boolean',
        ];
    }

    /** Affectations en cours à une date (aujourd'hui par défaut). */
    public function scopeActive(Builder $query, ?Carbon $on = null): Builder
    {
        $on ??= today();

        return $query->whereDate('start_date', '<=', $on)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $on));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WorkSite::class, 'work_site_id');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reports_to_employee_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function state(): string
    {
        if ($this->start_date->gt(today())) {
            return 'upcoming';
        }

        return ($this->end_date && $this->end_date->lt(today())) ? 'past' : 'active';
    }
}
