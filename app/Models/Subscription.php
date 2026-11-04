<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    public const STATUSES = [
        'trial' => 'Essai gratuit', 'pending' => 'En attente de paiement', 'active' => 'Actif', 'past_due' => 'Paiement en retard',
        'suspended' => 'Suspendu', 'expired' => 'Expiré', 'cancelled' => 'Annulé', 'replaced' => 'Remplacé',
    ];

    /** États dans lesquels l'entreprise utilise normalement l'application. */
    public const USABLE = ['trial', 'pending', 'active', 'past_due'];

    protected $fillable = [
        'company_id', 'plan_id', 'period_months', 'status', 'starts_at', 'ends_at', 'trial_ends_at',
        'list_price', 'discount_percent', 'price', 'currency', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'trial_ends_at' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function payments(): HasMany { return $this->hasMany(SubscriptionPayment::class); }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Équivalent mensuel du prix net (sert au revenu mensuel récurrent). */
    public function monthlyAmount(): int
    {
        return $this->period_months > 0 ? intdiv((int) $this->price, (int) $this->period_months) : 0;
    }
}
