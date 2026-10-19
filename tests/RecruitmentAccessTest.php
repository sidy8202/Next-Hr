<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\RecruitmentFixtures;
use Tests\TestCase;

class RecruitmentAccessTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    public function test_sans_droit_la_banque_de_cv_est_interdite(): void
    {
        $user = $this->makeUser(owner: false); // aucun droit accordé

        $this->actingAs($user)->get(route('candidates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('job-offers.index'))->assertForbidden();
    }

    public function test_le_proprietaire_accede_aux_pages_du_recrutement(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('candidates.index'))->assertOk();
        $this->actingAs($user)->get(route('candidates.create'))->assertOk();
        $this->actingAs($user)->get(route('job-offers.index'))->assertOk();
        $this->actingAs($user)->get(route('job-offers.create'))->assertOk();
    }

    public function test_le_profil_d_une_autre_entreprise_est_introuvable(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $foreign = $this->makeCandidate($b);

        $this->actingAs($a)->get(route('candidates.edit', $foreign))->assertNotFound();
        $this->actingAs($a)->get(route('candidates.cv', $foreign))->assertNotFound();
    }

    public function test_l_offre_d_une_autre_entreprise_est_introuvable(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $foreign = $this->makeOffer($b);

        $this->actingAs($a)->get(route('job-offers.show', $foreign))->assertNotFound();
    }
}
