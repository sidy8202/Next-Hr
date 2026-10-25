<?php

namespace Tests\Feature;

use App\Mail\ApplicationRejectedNotice;
use App\Mail\InterviewNotice;
use App\Models\Application;
use App\Models\Company;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\CandidateNotifier;
use App\Services\InterviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\RecruitmentFixtures;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    private function application(User $user, string $stage = 'screening', array $candidate = []): Application
    {
        $app = app(ApplicationService::class)->apply($this->makeOffer($user), $this->makeCandidate($user, $candidate));

        return $stage === 'new' ? $app : app(ApplicationService::class)->move($app, 'screening');
    }

    private function slot(string $when = '+2 days 10:00'): string
    {
        return now()->modify($when)->format('Y-m-d H:i');
    }

    private function data(array $over = []): array
    {
        return array_merge(['scheduled_at' => $this->slot(), 'duration_minutes' => 60, 'mode' => 'onsite', 'location' => 'Salle 2'], $over);
    }

    public function test_planifier_un_entretien_fait_passer_la_candidature_en_entretien(): void
    {
        $user = $this->makeUser();
        $app = $this->application($user);

        $interview = app(InterviewService::class)->schedule($app, $this->data());

        $this->assertSame('scheduled', $interview->status);
        $this->assertSame('interview', $app->fresh()->stage);
    }

    public function test_un_creneau_passe_est_refuse(): void
    {
        $user = $this->makeUser();
        $app = $this->application($user);

        $this->expectException(ValidationException::class);
        app(InterviewService::class)->schedule($app, $this->data(['scheduled_at' => now()->subDay()->format('Y-m-d H:i')]));
    }

    public function test_une_candidature_terminee_n_a_plus_d_entretien(): void
    {
        $user = $this->makeUser();
        $app = $this->application($user);
        app(ApplicationService::class)->move($app, 'rejected', null, 'Profil ne correspondant pas');

        $this->expectException(ValidationException::class);
        app(InterviewService::class)->schedule($app, $this->data());
    }

    public function test_un_evaluateur_n_a_pas_deux_entretiens_en_meme_temps(): void
    {
        $owner = $this->makeUser();
        $evaluator = $this->makeUser(false, Company::find($owner->company_id));
        $service = app(InterviewService::class);

        $service->schedule($this->application($owner), $this->data(['interviewer_id' => $evaluator->id]));

        // Créneau voisin (qui commence quand l'autre finit) : permis.
        $service->schedule($this->application($owner), $this->data(['interviewer_id' => $evaluator->id, 'scheduled_at' => $this->slot('+2 days 11:00')]));

        // Chevauchement : refusé.
        $this->expectException(ValidationException::class);
        $service->schedule($this->application($owner), $this->data(['interviewer_id' => $evaluator->id, 'scheduled_at' => $this->slot('+2 days 10:30')]));
    }

    public function test_un_candidat_n_a_pas_deux_entretiens_en_meme_temps(): void
    {
        $user = $this->makeUser();
        $app = $this->application($user);
        $service = app(InterviewService::class);

        $service->schedule($app, $this->data());

        $this->expectException(ValidationException::class);
        $service->schedule($app, $this->data(['scheduled_at' => $this->slot('+2 days 10:30')]));
    }

    public function test_deplacer_un_entretien_ne_le_met_pas_en_conflit_avec_lui_meme(): void
    {
        $user = $this->makeUser();
        $service = app(InterviewService::class);
        $interview = $service->schedule($this->application($user), $this->data());

        $moved = $service->reschedule($interview, $this->data(['scheduled_at' => $this->slot('+2 days 10:30')]));

        $this->assertSame('10:30', $moved->scheduled_at->format('H:i'));
    }

    public function test_un_entretien_annule_n_est_plus_modifiable(): void
    {
        $user = $this->makeUser();
        $service = app(InterviewService::class);
        $interview = $service->cancel($service->schedule($this->application($user), $this->data()));

        $this->assertSame('cancelled', $interview->status);

        $this->expectException(ValidationException::class);
        $service->reschedule($interview, $this->data());
    }

    public function test_la_cloture_exige_note_et_compte_rendu_et_un_entretien_passe(): void
    {
        $user = $this->makeUser();
        $service = app(InterviewService::class);
        $app = $this->application($user);

        $future = $service->schedule($app, $this->data());

        try {
            $service->complete($future, 'done', 4, 'Très bon entretien');
            $this->fail('Un entretien futur ne peut pas être clôturé.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $past = Interview::create([
            'application_id' => $app->id, 'scheduled_at' => now()->subDay(), 'duration_minutes' => 60,
            'mode' => 'onsite', 'status' => 'scheduled',
        ]);

        try {
            $service->complete($past, 'done', null, 'Compte rendu');
            $this->fail('La note est obligatoire.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $done = $service->complete($past, 'done', 4, 'Bon profil technique');

        $this->assertSame('done', $done->status);
        $this->assertSame(4, $done->score);
    }

    public function test_l_evaluateur_cloture_son_entretien_mais_pas_celui_d_un_autre(): void
    {
        $owner = $this->makeUser();
        $company = Company::find($owner->company_id);
        $evaluator = $this->makeUser(false, $company);   // aucun droit accordé
        $other = $this->makeUser(false, $company);

        $app = $this->application($owner);
        $past = Interview::create([
            'application_id' => $app->id, 'scheduled_at' => now()->subDay(), 'duration_minutes' => 60,
            'mode' => 'onsite', 'status' => 'scheduled', 'interviewer_id' => $evaluator->id,
        ]);

        $this->actingAs($other);
        try {
            app(InterviewService::class)->complete($past, 'no_show');
            $this->fail('Un autre utilisateur ne doit pas pouvoir clôturer.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->actingAs($evaluator);
        $done = app(InterviewService::class)->complete($past, 'no_show');
        $this->assertSame('no_show', $done->status);
    }

    public function test_les_emails_partent_au_candidat_et_a_l_evaluateur(): void
    {
        Mail::fake();
        $owner = $this->makeUser();
        $evaluator = $this->makeUser(false, Company::find($owner->company_id));
        $this->actingAs($owner);

        $interview = app(InterviewService::class)->schedule(
            $this->application($owner, 'screening', ['email' => 'candidat@example.com']),
            $this->data(['interviewer_id' => $evaluator->id])
        );

        $status = app(CandidateNotifier::class)->interview($interview, 'scheduled');

        $this->assertSame(['candidate' => 'sent', 'interviewer' => 'sent'], $status);
        Mail::assertSent(InterviewNotice::class, 2);
    }

    public function test_sans_adresse_aucun_email_n_est_envoye(): void
    {
        Mail::fake();
        $owner = $this->makeUser();
        $interview = app(InterviewService::class)->schedule($this->application($owner, 'screening', ['email' => null, 'phone' => '70000000']), $this->data());

        $status = app(CandidateNotifier::class)->interview($interview, 'scheduled');

        $this->assertSame(['candidate' => 'none', 'interviewer' => 'none'], $status);
        Mail::assertNothingSent();
    }

    public function test_l_avis_de_refus_ne_contient_pas_le_motif_interne(): void
    {
        Mail::fake();
        $owner = $this->makeUser();
        $app = $this->application($owner, 'screening', ['email' => 'candidat@example.com']);
        $app = app(ApplicationService::class)->move($app, 'rejected', null, 'Expérience insuffisante');

        $this->assertSame('sent', app(CandidateNotifier::class)->rejected($app));

        Mail::assertSent(ApplicationRejectedNotice::class, fn ($mail) => ! str_contains($mail->render(), 'Expérience insuffisante'));
    }
}
