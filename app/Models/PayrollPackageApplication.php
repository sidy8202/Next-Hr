<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une application de paquet par une entreprise. Pas de scope global : on filtre toujours par company_id. */
class PayrollPackageApplication extends Model
{
    protected $fillable = [
        'payroll_package_id', 'package_code', 'package_name', 'package_version', 'was_validated', 'applied_by', 'report',
    ];

    protected function casts(): array
    {
        return ['report' => 'array', 'was_validated' => 'boolean', 'package_version' => 'integer'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PayrollPackage::class, 'payroll_package_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
