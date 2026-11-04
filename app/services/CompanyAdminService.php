<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyAdminService
{
    /** Crée l'entreprise et son compte propriétaire. Retourne [Company, mot de passe provisoire à transmettre]. */
    public function create(array $data, array $owner, User $by): array
    {
        abort_unless($by->platformCan('companies.manage'), 403);

        if (User::where('email', $owner['email'])->exists()) {
            throw ValidationException::withMessages(['owner_email' => 'Cette adresse e-mail est déjà utilisée par un compte.']);
        }

        $temp = Str::random(9).random_int(10, 99);

        $company = DB::transaction(function () use ($data, $owner, $temp) {
            $company = Company::create($data + ['slug' => $this->uniqueSlug($data['name']), 'status' => 'trial']);

            $user = new User();
            $user->forceFill([
                'name' => $owner['name'], 'email' => $owner['email'], 'password' => $temp, 'company_id' => $company->id,
                'is_company_owner' => true, 'is_active' => true, 'is_platform_admin' => false, 'must_change_password' => true,
            ])->save();

            return $company;
        });

        PlatformAudit::log('company_created', "Entreprise « {$company->name} » créée", $company->id, ['owner' => $owner['email']]);

        return [$company, $temp];
    }

    public function update(Company $company, array $data, User $by): Company
    {
        abort_unless($by->platformCan('companies.manage'), 403);

        $company->update($data);
        PlanGate::flush($company->id);
        PlatformAudit::log('company_updated', "Entreprise « {$company->name} » modifiée", $company->id);

        return $company;
    }

    public function suspend(Company $company, string $reason, User $by): void
    {
        abort_unless($by->platformCan('companies.suspend'), 403);

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Indiquez le motif de la suspension (5 caractères minimum).']);
        }

        $company->update(['status' => 'suspended', 'suspended_at' => now(), 'suspension_reason' => trim($reason)]);
        PlanGate::flush($company->id);
        PlatformAudit::log('company_suspended', "Entreprise « {$company->name} » suspendue : {$reason}", $company->id);
    }

    public function reactivate(Company $company, User $by): void
    {
        abort_unless($by->platformCan('companies.suspend'), 403);

        $company->update(['status' => 'active', 'suspended_at' => null, 'suspension_reason' => null]);
        PlanGate::flush($company->id);
        PlatformAudit::log('company_reactivated', "Entreprise « {$company->name} » réactivée", $company->id);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'entreprise';
        $slug = $base;
        $i = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
