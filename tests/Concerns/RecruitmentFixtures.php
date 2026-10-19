<?php

namespace Tests\Feature\Concerns;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\User;

/**
 * Données de test du recrutement.
 * ADAPTER makeUser() à votre façon habituelle de créer une entreprise et un utilisateur
 * (copiez la mise en place de tests/Feature/TenantIsolationTest.php si elle diffère).
 */
trait RecruitmentFixtures
{
    protected function makeUser(bool $owner = true, ?Company $company = null): User
    {
        $company ??= Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'is_company_owner' => $owner, // le propriétaire a tous les droits
        ]);
    }

    protected function makeCandidate(User $as, array $overrides = []): Candidate
    {
        $this->actingAs($as); // l'entreprise est remplie automatiquement depuis l'utilisateur connecté

        return Candidate::create(array_merge([
            'first_name' => 'Awa',
            'last_name' => 'Traoré',
            'email' => fake()->unique()->safeEmail(),
            'education_level' => 'bac3',
            'experience_years' => 4,
            'profile_category' => 'Informatique & Télécoms',
            'status' => 'active',
        ], $overrides));
    }

    protected function makeOffer(User $as, array $overrides = []): JobOffer
    {
        $this->actingAs($as);

        return JobOffer::create(array_merge([
            'reference' => 'OFF-'.fake()->unique()->numerify('####-###'),
            'title' => 'Technicien réseau',
            'contract_type' => 'CDI',
            'min_experience_years' => 0,
            'vacancies' => 1,
            'status' => 'open',
        ], $overrides));
    }
}
