<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStep extends Model
{
    protected $fillable = ['salary_grade_id', 'label', 'amount', 'sort'];

    public function grade(): BelongsTo { return $this->belongsTo(SalaryGrade::class, 'salary_grade_id'); }
}
