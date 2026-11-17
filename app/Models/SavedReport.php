<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** Rapport enregistré par un utilisateur : un rapport de la bibliothèque et ses critères. Privé ou partagé dans l'entreprise. */
class SavedReport extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
