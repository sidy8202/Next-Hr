<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    protected $fillable = ['salary_grid_id', 'code', 'label', 'sort'];

    public function grid(): BelongsTo { return $this->belongsTo(SalaryGrid::class, 'salary_grid_id'); }

    public function steps(): HasMany
    {
        return $this->hasMany(SalaryStep::class)->orderBy('sort')->orderBy('id');
    }
}
