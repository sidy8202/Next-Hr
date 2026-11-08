<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = ['country', 'convention_name', 'days_basis', 'hours_month', 'rounding', 'show_employer_part'];

    protected function casts(): array
    {
        return ['show_employer_part' => 'boolean', 'hours_month' => 'float'];
    }

    public static function forCompany(int $companyId): self
    {
        $s = static::where('company_id', $companyId)->first();

        if (! $s) {
            $s = new static();
            $s->company_id = $companyId;
            $s->save();
            $s->refresh();
        }

        return $s;
    }
}
