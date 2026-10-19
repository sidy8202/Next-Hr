<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Candidate;
use App\Services\ApplicationService;
use App\Services\JobOfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\RecruitmentFixtures;
use Tests\TestCase;

class RecruitmentPipelineTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    private function walkTo(Application $app, array $stages): Application
    {
        foreach ($stages as $stage) {
            $app = app(ApplicationService::class)->move($app, $stage);
        }

        return $app;
    }

    public function test_une_candidature_est_impossible_sur_une_offre_fermee(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user, ['status' => 'closed']);
        $candidate = $this->makeCandidate($user);

        $this->expectException(ValidationException::class);
        app(ApplicationService::class)->apply($offer, $candidate);
    }

    public function test_un_profil_ne_postule_qu_une_fois_a_la_meme_offre(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user);
        $candidate = $this->makeCandidate($user);

        app(ApplicationService::class)->apply($offer, $candidate);

        $this->expectException(ValidationException::class);
        app(ApplicationService::class)->apply($offer, $candidate);
    }

    public function test_on_ne_saute_pas_d_etape(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user);
        $app = app(ApplicationService::class)->apply($offer, $this->makeCandidate($user));

        $this->expectException(ValidationException::class);
        app(ApplicationService::class)->move($app, 'hired');
    }

    public function test_un_refus_exige_un_motif(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user);
        $app = app(ApplicationService::class)->apply($offer, $this->makeCandidate($user));

        $this->expectException(ValidationException::class);
        app(ApplicationService::class)->move($app, 'rejected', null, '');
    }

    public function test_un_refus_est_enregistre_avec_son_motif_et_son_historique(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user);
        $app = app(ApplicationService::class)->apply($offer, $this->makeCandidate($user));

        $app = app(ApplicationService::class)->move($app, 'rejected', null, 'Expérience insuffisante');

        $this->assertSame('rejected', $app->stage);
        $this->assertSame('Expérience insuffisante', $app->rejection_reason);
        $this->assertSame(2, $app->events()->count()); // dépôt + refus
    }

    public function test_l_embauche_pourvoit_l_offre_et_le_profil(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user, ['vacancies' => 1]);
        $candidate = $this->makeCandidate($user);
        $app = app(ApplicationService::class)->apply($offer, $candidate);

        $app = $this->walkTo($app, ['screening', 'interview', 'offer', 'hired']);

        $this->assertSame('hired', $app->stage);
        $this->assertSame('filled', $offer->fresh()->status);
        $this->assertSame('hired', $candidate->fresh()->status);
    }

    public function test_on_n_embauche_pas_plus_que_de_postes(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user, ['vacancies' => 2]);
        $service = app(ApplicationService::class);

        $first = $service->apply($offer, $this->makeCandidate($user));
        $second = $service->apply($offer, $this->makeCandidate($user));
        $third = $service->apply($offer, $this->makeCandidate($user));

        foreach ([$first, $second, $third] as $app) {
            $this->walkTo($app, ['screening', 'interview', 'offer']);
        }

        $service->move($first, 'hired');
        $service->move($second, 'hired');
        $this->assertSame('filled', $offer->fresh()->status);

        $this->expectException(ValidationException::class);
        $service->move($third, 'hired');
    }

    public function test_une_offre_pourvue_ne_se_rouvre_pas_sans_poste_libre(): void
    {
        $user = $this->makeUser();
        $offer = $this->makeOffer($user, ['vacancies' => 1]);
        $app = app(ApplicationService::class)->apply($offer, $this->makeCandidate($user));
        $this->walkTo($app, ['screening', 'interview', 'offer', 'hired']);

        $offers = app(JobOfferService::class);
        $offers->setStatus($offer->fresh(), 'closed');

        $this->expectException(ValidationException::class);
        $offers->setStatus($offer->fresh(), 'open');
    }

    public function test_les_candidatures_d_une_autre_entreprise_sont_invisibles(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $offer = $this->makeOffer($b);
        app(ApplicationService::class)->apply($offer, $this->makeCandidate($b));

        $this->actingAs($a);
        $this->assertSame(0, Application::count());
        $this->assertSame(0, Candidate::count());
    }

    public function test_la_purge_supprime_les_profils_expires_sauf_embauches_et_en_cours(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $expired = $this->makeCandidate($user, ['retention_until' => now()->subDay()->toDateString()]);
        $valid = $this->makeCandidate($user, ['retention_until' => now()->addMonth()->toDateString()]);
        $hired = $this->makeCandidate($user, ['retention_until' => now()->subDay()->toDateString(), 'status' => 'hired']);
        $inProgress = $this->makeCandidate($user, ['retention_until' => now()->subDay()->toDateString()]);
        app(ApplicationService::class)->apply($this->makeOffer($user), $inProgress);

        $this->artisan('recruitment:purge-expired')->assertSuccessful();

        $remaining = Candidate::withoutGlobalScopes()->pluck('id')->all();
        $this->assertNotContains($expired->id, $remaining);
        $this->assertContains($valid->id, $remaining);
        $this->assertContains($hired->id, $remaining);
        $this->assertContains($inProgress->id, $remaining);
    }
}
