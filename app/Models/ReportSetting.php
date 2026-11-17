<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** Seuil de lecture des rapports, par entreprise. */
class ReportSetting extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];
}
