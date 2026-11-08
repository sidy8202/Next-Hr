<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayProfileItem extends Model
{
    protected $fillable = ['pay_profile_id', 'pay_item_id', 'amount', 'rate'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PayProfile::class, 'pay_profile_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayItem::class, 'pay_item_id');
    }
}
