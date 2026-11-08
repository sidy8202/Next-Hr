<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRule extends Model
{
    protected $fillable = [
        'country', 'code', 'label', 'party', 'kind', 'rate', 'params', 'base', 'deductible_from_its', 'ceiling_monthly',
        'effective_from', 'effective_until', 'status', 'validated_by', 'validated_at', 'source', 'notes', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array', 'rate' => 'float', 'deductible_from_its' => 'boolean',
            'effective_from' => 'date', 'effective_until' => 'date', 'validated_at' => 'datetime',
        ];
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** Règles en vigueur à une date : pour chaque code, la version la plus récente dont la date d'effet est passée et non expirée. */
    public static function activeOn(string $date, string $country = 'ML'): array
    {
        $rows = static::where('country', $country)->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->orderBy('effective_from')->get();

        return $rows->groupBy('code')->map->last()->sortBy('sort')->values()->map(fn (self $r) => [
            'code' => $r->code, 'label' => $r->label, 'party' => $r->party, 'kind' => $r->kind, 'rate' => $r->rate, 'params' => $r->params,
            'base' => $r->base, 'deductible_from_its' => $r->deductible_from_its, 'ceiling_monthly' => $r->ceiling_monthly, 'status' => $r->status,
        ])->all();
    }
}
