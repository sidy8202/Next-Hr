<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToCompany;

    public const STATUSES = ['draft' => 'À calculer', 'calculated' => 'Calculée', 'closed' => 'Clôturée'];

    protected $fillable = ['period', 'status', 'calculated_at', 'calculated_by', 'closed_at', 'closed_by', 'summary', 'notes'];

    protected function casts(): array
    {
        return ['calculated_at' => 'datetime', 'closed_at' => 'datetime', 'summary' => 'array'];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
