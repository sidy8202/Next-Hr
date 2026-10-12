<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Filtre automatique : toutes les requêtes sont limitées à l'entreprise de l'utilisateur connecté
        static::addGlobalScope('company', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->company_id) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $user->company_id);
            }
        });

        // À la création : company_id est rempli automatiquement
        static::creating(function ($model) {
            if (empty($model->company_id) && Auth::check() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
