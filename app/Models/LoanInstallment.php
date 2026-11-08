<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    public const STATUSES = ['scheduled' => 'Programmée', 'suspended' => 'Suspendue', 'deducted' => 'Prélevée', 'prepaid' => 'Remboursée par anticipation'];

    protected $fillable = ['employee_loan_id', 'due_month', 'amount', 'status', 'deducted_at', 'deducted_ref'];

    protected function casts(): array
    {
        return ['deducted_at' => 'datetime'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    /** À appeler à la VALIDATION de la paie (lot 14a), jamais à un simple calcul. */
    public function markDeducted(?string $reference = null): void
    {
        $this->update(['status' => 'deducted', 'deducted_at' => now(), 'deducted_ref' => $reference]);
        $this->loan?->syncStatus();
    }
}
