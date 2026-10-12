<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    //
    protected $fillable = [
        'user_id',
        'resource',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
        'scope',
    ];

    protected $casts = [
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * L'utilisateur propriétaire de la permission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
