<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayItemScaleRow extends Model
{
    protected $fillable = ['pay_item_id', 'from_years', 'rate'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayItem::class, 'pay_item_id');
    }
}
