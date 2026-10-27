<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** Base ou site de travail (Bamako, Sikasso, Ségou…). */
class WorkSite extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'city', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
