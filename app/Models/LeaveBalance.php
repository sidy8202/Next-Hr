<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToCompany;

     /**
     * Protection contre le mass-assignment :
     * 'id' et 'company_id' sont verrouillés, tous les autres champs sont autorisés.
     */
    protected $guarded = ['id', 'company_id'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    protected function remaining(): Attribute
    {
        return Attribute::get(fn () => ($this->acquired + $this->carried_over) - $this->taken);
    }
}
