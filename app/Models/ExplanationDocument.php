<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ExplanationDocument extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    public const KINDS = ['request' => 'Demande d’explication', 'response' => 'Réponse de l’employé', 'decision' => 'Décision'];

    protected $fillable = ['case_id', 'kind', 'reference', 'file_path', 'document_id', 'created_by'];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
