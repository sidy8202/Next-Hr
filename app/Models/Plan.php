<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'description', 'color', 'max_employees', 'storage_gb', 'audit_retention_months', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function prices(): HasMany { return $this->hasMany(PlanPrice::class); }
    public function features(): HasMany { return $this->hasMany(PlanFeature::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }

    public function priceFor(int $months): ?int
    {
        $p = $this->prices->firstWhere('months', $months)?->price;

        return $p === null ? null : (int) $p;
    }

    public function hasFeature(string $key): bool
    {
        return (bool) $this->features->firstWhere('feature', $key)?->enabled;
    }
}
