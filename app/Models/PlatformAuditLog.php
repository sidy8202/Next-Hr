<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Journal en ajout seul : aucune ligne ne se modifie ni ne se supprime depuis l'application. */
class PlatformAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'company_id', 'action', 'summary', 'meta', 'ip', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
