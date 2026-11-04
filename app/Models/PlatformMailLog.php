<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformMailLog extends Model
{
    public $timestamps = false;

    public const TYPES = [
        'invoice' => 'Facture', 'reminder_30' => 'Rappel d’échéance (30 jours)', 'reminder_7' => 'Rappel d’échéance (7 jours)',
        'reminder_1' => 'Rappel d’échéance (dernier jour)', 'trial_7' => 'Fin d’essai (7 jours)', 'trial_3' => 'Fin d’essai (3 jours)',
        'trial_1' => 'Fin d’essai (dernier jour)', 'past_due' => 'Paiement en retard', 'expired' => 'Abonnement expiré', 'test' => 'Test d’envoi',
    ];

    protected $fillable = ['company_id', 'subscription_id', 'payment_id', 'type', 'period_end', 'recipients', 'subject', 'status', 'error', 'triggered_by', 'created_at'];

    protected function casts(): array
    {
        return ['period_end' => 'date', 'created_at' => 'datetime'];
    }

    public function triggeredBy(): BelongsTo { return $this->belongsTo(User::class, 'triggered_by'); }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
