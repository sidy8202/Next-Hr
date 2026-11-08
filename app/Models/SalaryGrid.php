<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrid extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'convention', 'effective_from', 'is_active'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'is_active' => 'boolean'];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(SalaryGrade::class)->orderBy('sort')->orderBy('id');
    }
}
