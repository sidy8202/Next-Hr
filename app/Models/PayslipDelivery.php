<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipDelivery extends Model
{
    use BelongsToCompany;

    public const CHANNELS = ['work_email' => 'E-mail professionnel', 'personal_email' => 'E-mail personnel', 'app' => 'Application'];

    protected $fillable = [
        'payslip_id', 'employee_id', 'channel', 'address', 'status', 'attempts', 'sent_at', 'error', 'token', 'opened_at', 'viewed_at', 'triggered_by',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'opened_at' => 'datetime', 'viewed_at' => 'datetime'];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
