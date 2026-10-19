<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use BelongsToCompany;

    /**
     * Protection contre le mass-assignment :
     * 'id' et 'company_id' sont verrouillés, tous les autres champs sont autorisés.
     */
    protected $guarded = ['id', 'company_id'];

    /**
     * Un document appartient à un employé spécifique.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
