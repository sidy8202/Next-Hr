<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id', 'company_id', 'invoice_number', 'amount', 'paid_at', 'method', 'reference',
        'period_start', 'period_end', 'note', 'recorded_by', 'cycle', 'tranche', 'balance_after',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'period_start' => 'date', 'period_end' => 'date'];
    }

    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
