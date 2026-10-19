<?php

namespace Tests\Feature;

use App\Livewire\Recruitment\CandidateForm;
use App\Livewire\Recruitment\CandidateIndex;
use App\Models\Candidate;
use App\Services\CandidateService;
use App\Support\CandidateProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Concerns\RecruitmentFixtures;
use Tests\TestCase;

class CandidateBankTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    private function fillForm($component, array $over = [])
    {
        $values = array_merge([
            'first_name' => 'Awa', 'last_name' => 'Traoré', 'email' => 'awa@example.com',
            'education_level' => 'bac3', 'experience_years' => 4,
            'profile_category' => 'Informatique & Télécoms', 'consent' => true,
        ], $over);

        foreach ($values as $key => $value) {
            $component->set($key, $value);
        }

        return $component;
    }

    public function test_l_anciennete_vient_des_annees_d_experience(): void
    {
        $this->assertSame('junior', CandidateProfile::seniority(1.5));
        $this->assertSame('confirmed', CandidateProfile::seniority(2));
        $this->assertSame('confirmed', CandidateProfile::seniority(5.5));
        $this->assertSame('senior', CandidateProfile::seniority(6));
        $this->assertSame('expert', CandidateProfile::seniority(12));
    }

    public function test_les_niveaux_d_etudes_se_comparent(): void
    {
        $this->assertSame(['bac3', 'bac5', 'bac8'], CandidateProfile::levelsAtLeast('bac3'));
    }

    public function test_un_cv_est_range_dans_le_dossier_prive(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $component = Livewire::actingAs($user)->test(CandidateForm::class);
        $this->fillForm($component)
            ->set('cv', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $candidate = Candidate::firstOrFail();
        $this->assertStringStartsWith("companies/{$user->company_id}/candidates/", $candidate->cv_path);
        Storage::disk('local')->assertExists($candidate->cv_path);
        $this->assertNotNull($candidate->consent_given_at);
        $this->assertNotNull($candidate->retention_until);
    }

    public function test_un_fichier_non_autorise_est_refuse(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $component = Livewire::actingAs($user)->test(CandidateForm::class);
        $this->fillForm($component)
            ->set('cv', UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'))
            ->call('save')
            ->assertHasErrors(['cv']);

        $this->assertSame(0, Candidate::count());
    }

    public function test_le_consentement_est_obligatoire(): void
    {
        $user = $this->makeUser();

        $component = Livewire::actingAs($user)->test(CandidateForm::class);
        $this->fillForm($component, ['consent' => false])->call('save')->assertHasErrors(['consent']);

        $this->assertSame(0, Candidate::count());
    }

    public function test_il_faut_un_email_ou_un_telephone(): void
    {
        $user = $this->makeUser();

        $component = Livewire::actingAs($user)->test(CandidateForm::class);
        $this->fillForm($component, ['email' => '', 'phone' => ''])->call('save')->assertHasErrors(['email', 'phone']);
    }

    public function test_les_competences_sont_normalisees(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $candidate = app(CandidateService::class)->create([
            'first_name' => 'Awa', 'last_name' => 'Traoré', 'email' => 'a@example.com',
            'education_level' => 'bac3', 'experience_years' => 3,
            'profile_category' => 'Informatique & Télécoms', 'skills' => 'Excel, Réseaux; excel ,  SQL',
        ]);

        $this->assertSame(['excel', 'réseaux', 'sql'], $candidate->fresh()->skills);
    }

    public function test_les_filtres_croisent_domaine_anciennete_et_etudes(): void
    {
        $user = $this->makeUser();
        $this->makeCandidate($user, ['first_name' => 'Junior', 'last_name' => 'Info', 'experience_years' => 1]);
        $this->makeCandidate($user, ['first_name' => 'Senior', 'last_name' => 'Info', 'experience_years' => 7]);
        $this->makeCandidate($user, ['first_name' => 'Senior', 'last_name' => 'Compta', 'experience_years' => 8, 'profile_category' => 'Finance & Comptabilité']);

        Livewire::actingAs($user)->test(CandidateIndex::class)
            ->set('seniority', 'senior')->set('profile', 'Informatique & Télécoms')
            ->assertSee('Senior Info')
            ->assertDontSee('Junior Info')
            ->assertDontSee('Senior Compta');
    }

    public function test_un_profil_avec_candidatures_ne_se_supprime_pas(): void
    {
        $user = $this->makeUser();
        $candidate = $this->makeCandidate($user);
        $offer = $this->makeOffer($user);
        app(\App\Services\ApplicationService::class)->apply($offer, $candidate);

        $this->expectException(ValidationException::class);
        app(CandidateService::class)->delete($candidate);
    }

    public function test_le_meme_email_est_permis_dans_deux_entreprises_mais_pas_dans_la_meme(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->makeCandidate($b, ['email' => 'commun@example.com']);

        $component = Livewire::actingAs($a)->test(CandidateForm::class);
        $this->fillForm($component, ['email' => 'commun@example.com'])->call('save')->assertHasNoErrors();

        $component = Livewire::actingAs($a)->test(CandidateForm::class);
        $this->fillForm($component, ['email' => 'commun@example.com', 'first_name' => 'Doublon'])->call('save')->assertHasErrors(['email']);
    }
}
