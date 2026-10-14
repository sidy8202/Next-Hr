<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


use Illuminate\Database\Eloquent\Model;

class MissionOrder extends Model
{
    //
    public function requester(): BelongsTo { return $this->belongsTo(Employee::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function travelers(): HasMany { return $this->hasMany(MissionOrderEmployee::class); }
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'mission_order_employees')
            ->withPivot(['per_diem', 'advance_amount', 'actual_expenses']);
    }
}
