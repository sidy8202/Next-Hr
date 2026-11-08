<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    use BelongsToCompany;

    public const TYPES = ['loan' => 'Prêt', 'advance' => 'Avance sur salaire'];

    public const STATUSES = [
        'requested' => 'Demandé',
        'approved' => 'Approuvé',
        'active' => 'En cours',
        'suspended' => 'Suspendu',
        'paid' => 'Soldé',
        'cancelled' => 'Annulé',
    ];

    protected $fillable = [
        'employee_id', 'type', 'label', 'principal', 'installment_amount', 'first_month', 'interest_rate', 'status',
        'approved_by', 'approved_at', 'disbursed_on', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'disbursed_on' => 'date', 'interest_rate' => 'float'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('due_month');
    }

    /** Solde restant dû : somme des échéances pas encore prélevées (programmées ou suspendues). */
    public function remaining(): int
    {
        return (int) $this->installments()->whereIn('status', ['scheduled', 'suspended'])->sum('amount');
    }

    /** Passe le prêt à « Soldé » quand plus aucune échéance n'est à prélever. À rappeler après chaque prélèvement validé (lot 14a). */
    public function syncStatus(): void
    {
        if (in_array($this->status, ['active', 'suspended'], true)
            && ! $this->installments()->whereIn('status', ['scheduled', 'suspended'])->exists()) {
            $this->update(['status' => 'paid']);
        }
    }
}
