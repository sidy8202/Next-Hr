<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


use Illuminate\Database\Eloquent\Model;

class MissionOrderEmployee extends Model
{
    //
    public function missionOrder(): BelongsTo {
         return $this->belongsTo(MissionOrder::class);
         }
    public function employee(): BelongsTo {
         return $this->belongsTo(Employee::class);
         }
}
