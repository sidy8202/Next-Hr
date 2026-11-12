<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use BelongsToCompany;

    public const CHECKS = [
        'ok' => 'Conforme',
        'variables' => 'Expliqué par les variables',
        'to_check' => 'À vérifier',
        'no_reference' => 'Net connu absent',
    ];

    protected $fillable = [
        'payroll_period_id', 'employee_id', 'period', 'status', 'employee_name', 'matricule', 'department_id', 'department_name',
        'gross', 'net_to_pay', 'net_contract', 'net_known', 'variables_effect', 'structure_gap', 'check_status',
        'employee_contributions', 'its', 'employer_total', 'employer_cost',
        'acknowledged_by', 'acknowledged_at', 'acknowledged_note', 'input_hash', 'snapshot',
    ];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'acknowledged_at' => 'datetime'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
