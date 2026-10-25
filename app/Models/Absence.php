<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use BelongsToCompany;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'days', 'justified', 'reason', 'deducted_days', 'justified_at', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'float',
            'justified' => 'boolean',
            'deducted_days' => 'float',
            'justified_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
