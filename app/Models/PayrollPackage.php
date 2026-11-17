<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Paquet par secteur (espace plateforme). Un paquet validé ne se modifie plus : on crée une nouvelle version. */
class PayrollPackage extends Model
{
    protected $fillable = [
        'code', 'name', 'sector', 'version', 'status', 'description', 'source', 'payload',
        'created_by', 'validated_by', 'validated_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'version' => 'integer', 'validated_at' => 'datetime'];
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(PayrollPackageApplication::class);
    }
}
