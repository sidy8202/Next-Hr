<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historique d'une candidature : on y accède toujours via Application (déjà filtrée par entreprise). */
class ApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['application_id', 'from_stage', 'to_stage', 'note', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
