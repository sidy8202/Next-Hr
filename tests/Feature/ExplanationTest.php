<?php

namespace Tests\Feature;

use App\Mail\ExplanationLetterMail;
use App\Models\Department;
use App\Models\ExplanationCase;
use App\Models\Letterhead;
use App\Models\Permission;
use App\Models\User;
use App\Services\ExplanationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class ExplanationTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private function grant(User $user, string $letters, string $scope): void
    {
        Permission::forceCreate([
            'user_id' => $user->id, 'resource' => 'explanation_letters', 'scope' => $scope,
            'can_create' => str_contains($letters, 'c'), 'can_read' => str_contains($letters, 'r'),
            'can_update' => str_contains($letters, 'u'), 'can_delete' => str_contains($letters, 'd'),
        ]);
    }

    /** @return array{0: User, 1: User, 2: User, 3: \App\Models\Employee} RH (propriétaire), chef, employé, fiche de l'employé */
    private function world(): array
    {
        $hr = $this->makeCompanyContext();
        $manager = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $manager->id]);
        $staff = $this->makeEmployee();
        $uManager = $this->makeAccount($manager);
        $uStaff = $this->makeAccount($staff);
        $this->grant($uManager, 'cr', 'team');
        $this->grant($uStaff, 'ru', 'own');

        return [$hr, $uManager, $uStaff, $staff];
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'subject' => 'Demande d’explication — absence non justifiée',
            'body' => "Nous avons constaté votre absence le 3 novembre sans justificatif.\n\nMerci de nous expliquer par écrit.",
            'response_days' => 3, 'copies' => 'Direction des Ressources Humaines', 'signatory_name' => 'Le Chef', 'signatory_title' => 'Chef de département',
        ], $over);
    }

    public function test_le_chef_emet_la_demande_et_la_rh_est_prevenue_en_meme_temps_que_l_employe(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world();

        $case = app(ExplanationService::class)->issue($uManager, $staff, $this->payload());

        $this->assertSame('awaiting_response', $case->status);
        $this->assertStringStartsWith('DE-'.now()->year.'-', $case->reference);
        $this->assertTrue($case->response_deadline->gt(today()));
        $this->assertSame(['request'], $case->documents->pluck('kind')->all());
        $this->assertDatabaseHas('documents', ['employee_id' => $staff->id, 'type' => 'explanation_request']);
        $this->assertGreaterThan(0, $uStaff->notifications()->count()); // l'employé
        $this->assertGreaterThan(0, $hr->notifications()->count());     // la RH, au même moment
        Mail::assertSent(ExplanationLetterMail::class);
    }

    public function test_un_chef_ne_peut_pas_ecrire_a_quelqu_un_hors_de_son_equipe(): void
    {
        [, $uManager] = $this->world();
        $stranger = $this->makeEmployee();
        \App\Models\Employee::whereKey($stranger->id)->update(['manager_id' => null]);

        $this->expectException(HttpException::class);
        app(ExplanationService::class)->issue($uManager, $stranger->fresh(), $this->payload());
    }

    public function test_on_ne_s_adresse_pas_une_demande_a_soi_meme(): void
    {
        [, , $uStaff, $staff] = $this->world();
        $this->grant(User::forceCreate(['company_id' => $staff->company_id, 'name' => 'x', 'email' => 'x@example.com', 'password' => 'secret-pass-123', 'is_active' => true, 'must_change_password' => false]), 'cr', 'all');
        Permission::where('user_id', $uStaff->id)->update(['can_create' => true, 'scope' => 'all']);

        $this->expectException(HttpException::class);
        app(ExplanationService::class)->issue($uStaff, $staff, $this->payload());
    }

    public function test_l_employe_repond_et_le_chef_et_la_rh_sont_prevenus(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world();
        $case = app(ExplanationService::class)->issue($uManager, $staff, $this->payload());
        $hrBefore = $hr->notifications()->count();
        $managerBefore = $uManager->notifications()->count();

        $this->actingAs($uStaff);
        $case = app(ExplanationService::class)->respond($case, $uStaff, 'J’étais absent pour raison médicale, certificat en cours.');

        $this->assertSame('responded', $case->status);
        $this->assertSame(['request', 'response'], $case->documents()->pluck('kind')->all());
        $this->assertGreaterThan($hrBefore, $hr->notifications()->count());
        $this->assertGreaterThan($managerBefore, $uManager->notifications()->count());
    }

    public function test_seul_l_employe_concerne_peut_repondre(): void
    {
        Storage::fake('local');
        Mail::fake();
        [, $uManager, , $staff] = $this->world();
        $case = app(ExplanationService::class)->issue($uManager, $staff, $this->payload());

        $this->expectException(HttpException::class);
        app(ExplanationService::class)->respond($case, $uManager, 'Je réponds à la place de l’employé.');
    }

    public function test_la_rh_decide_apres_la_reponse_et_la_lettre_de_decision_est_jointe_au_dossier(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());
        $this->actingAs($uStaff);
        $service->respond($case, $uStaff, 'Voici mes explications détaillées sur cette absence.');

        $this->actingAs($hr);
        $case = $service->decide($case->fresh(), $hr, ['type' => 'warning', 'subject' => 'Notification d’un avertissement', 'body' => 'Après examen de votre réponse, nous vous notifions un avertissement pour ces faits.']);

        $this->assertSame('decided', $case->status);
        $this->assertSame('warning', $case->decision_type);
        $this->assertSame(['request', 'response', 'decision'], $case->documents()->pluck('kind')->all());
        $this->assertDatabaseHas('documents', ['employee_id' => $staff->id, 'type' => 'explanation_decision']);
    }

    public function test_un_chef_ne_peut_pas_rendre_la_decision(): void
    {
        Storage::fake('local');
        Mail::fake();
        [, $uManager, $uStaff, $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());
        $this->actingAs($uStaff);
        $service->respond($case, $uStaff, 'Voici mes explications détaillées sur cette absence.');

        $this->expectException(HttpException::class);
        $service->decide($case->fresh(), $uManager, ['type' => 'warning', 'subject' => 'Avertissement', 'body' => 'Lettre de décision suffisamment longue pour passer.']);
    }

    public function test_pas_de_decision_avant_la_fin_du_delai_sans_reponse(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, , $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());
        $this->actingAs($hr);
        $decision = ['type' => 'caution', 'subject' => 'Lettre de mise en garde', 'body' => 'Faute de réponse dans le délai, nous vous adressons cette mise en garde.'];

        try {
            $service->decide($case, $hr, $decision);
            $this->fail('Le délai de réponse n’est pas écoulé.');
        } catch (ValidationException) {
            $this->assertSame('awaiting_response', $case->fresh()->status);
        }

        $case->update(['response_deadline' => today()->subDay()->toDateString()]);

        $this->assertSame('decided', $service->decide($case->fresh(), $hr, $decision)->status);
    }

    public function test_mise_a_pied_et_licenciement_exigent_leurs_dates(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());
        $this->actingAs($uStaff);
        $service->respond($case, $uStaff, 'Voici mes explications détaillées sur cette absence.');
        $this->actingAs($hr);
        $base = ['subject' => 'Notification', 'body' => 'Lettre de décision suffisamment longue pour passer la validation.'];

        foreach ([['type' => 'suspension'], ['type' => 'dismissal']] as $incomplete) {
            try {
                $service->decide($case->fresh(), $hr, $base + $incomplete);
                $this->fail('Les dates sont obligatoires.');
            } catch (ValidationException) {
                $this->assertSame('responded', $case->fresh()->status);
            }
        }

        $case = $service->decide($case->fresh(), $hr, $base + ['type' => 'suspension', 'suspension_start' => today()->addDay()->toDateString(), 'suspension_end' => today()->addDays(3)->toDateString(), 'suspension_paid' => false]);
        $this->assertSame('suspension', $case->decision_type);
    }

    public function test_l_auteur_peut_retirer_une_demande_sans_reponse(): void
    {
        Storage::fake('local');
        Mail::fake();
        [, $uManager, , $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());

        $case = $service->withdraw($case, $uManager, 'Émise par erreur');

        $this->assertSame('withdrawn', $case->status);
    }

    public function test_les_rappels_sont_envoyes_une_seule_fois(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());

        $case->update(['response_deadline' => today()->addDay()->toDateString()]);
        $before = $uStaff->notifications()->count();
        $this->assertSame(['reminded' => 1, 'overdue' => 0], $service->remind());
        $this->assertSame(['reminded' => 0, 'overdue' => 0], $service->remind());
        $this->assertGreaterThan($before, $uStaff->notifications()->count());

        $case->update(['response_deadline' => today()->subDay()->toDateString()]);
        $hrBefore = $hr->notifications()->count();
        $this->assertSame(['reminded' => 0, 'overdue' => 1], $service->remind());
        $this->assertGreaterThan($hrBefore, $hr->notifications()->count());
    }

    public function test_l_accuse_de_reception_n_est_pose_que_par_l_employe(): void
    {
        Storage::fake('local');
        Mail::fake();
        [, $uManager, $uStaff, $staff] = $this->world();
        $service = app(ExplanationService::class);
        $case = $service->issue($uManager, $staff, $this->payload());

        $service->markViewed($case->fresh(), $uManager);
        $this->assertNull($case->fresh()->employee_viewed_at);

        $service->markViewed($case->fresh(), $uStaff);
        $this->assertNotNull($case->fresh()->employee_viewed_at);
    }

    public function test_l_en_tete_reprend_le_nom_de_l_entreprise(): void
    {
        $hr = $this->makeCompanyContext();

        $this->assertNotEmpty(Letterhead::for($hr->company_id)->legal_name);
        $this->assertSame(3, Letterhead::for($hr->company_id)->default_response_days);
    }
}
