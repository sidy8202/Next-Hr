<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class OvertimeRate extends Model
{
    use BelongsToCompany;

    protected $fillable = ['label', 'percent', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'percent' => 'float'];
    }
}
