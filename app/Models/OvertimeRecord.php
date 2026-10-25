<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jour de repos (ou férié) travaillé, à compenser par un jour de récupération ou un ajout au solde de congé. */
class OvertimeRecord extends Model
{
    use BelongsToCompany;

    public const STATUSES = ['pending' => 'En attente', 'approved' => 'Validé', 'rejected' => 'Refusé', 'cancelled' => 'Annulé'];

    public const COMPENSATIONS = ['recovery' => 'Jour(s) de récupération à prendre', 'leave_balance' => 'Ajout au solde de congé'];

    protected $fillable = ['employee_id', 'worked_on', 'days', 'reason', 'status', 'compensation', 'credited_days', 'expires_on', 'decided_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'days' => 'float',
            'credited_days' => 'float',
            'expires_on' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
