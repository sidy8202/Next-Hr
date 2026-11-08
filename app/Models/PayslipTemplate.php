<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayslipTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'convention', 'show_employer_part', 'show_item_codes', 'group_lines', 'footer_text', 'is_default'];

    protected function casts(): array
    {
        return ['show_employer_part' => 'boolean', 'show_item_codes' => 'boolean', 'group_lines' => 'boolean', 'is_default' => 'boolean'];
    }
}
