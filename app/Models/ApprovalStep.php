<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use BelongsToCompany;

    public const KINDS = ['manager' => 'Chef de département', 'hr' => 'Ressources humaines'];

    public const ACTED_AS = [
        'manager' => 'Chef de département',
        'delegate' => 'Intérimaire du chef',
        'hr' => 'Ressources humaines',
        'hr_override' => 'RH, à la place du chef',
    ];

    protected $fillable = [
        'approvable_type', 'approvable_id', 'position', 'kind', 'status', 'decided_by', 'acted_as', 'comment', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
