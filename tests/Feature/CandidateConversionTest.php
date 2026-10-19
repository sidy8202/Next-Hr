<?php

namespace Tests\Feature;

use App\Livewire\Recruitment\ConvertCandidate;
use App\Mail\AccountCreated;
use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\CandidateConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Concerns\RecruitmentFixtures;
use Tests\TestCase;

class CandidateConversionTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    private Department $department;
    private Position $position;

    /**
     * ADAPTER si besoin : colonnes obligatoires de vos départements et postes
     * (copiez la façon de les créer dans DepartmentServiceTest).
     */
    private function hiredApplication(User $user, string $stage = 'hired'): Application
    {
        $this->actingAs($user);
        $this->department = Department::create(['name' => 'Informatique', 'code' => 'IT']);
        $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Technicien réseau']);

        $offer = $this->makeOffer($user, [
            'department_id' => $this->department->id, 'position_id' => $this->position->id, 'vacancies' => 1,
        ]);
        $service = app(ApplicationService::class);
        $app = $service->apply($offer, $this->makeCandidate($user, ['email' => 'awa@example.com']));

        foreach (['screening', 'interview', 'offer', 'hired'] as $next) {
            $app = $service->move($app, $next);

            if ($next === $stage) {
                break;
            }
        }

        return $app;
    }

    private function person(): array
    {
        return [
            'first_name' => 'Awa', 'last_name' => 'Traoré', 'gender' => 'female', 'birth_date' => '1995-04-12',
            'marital_status' => 'single', 'department_id' => $this->department->id, 'position_id' => $this->position->id,
            'hire_date' => now()->toDateString(), 'contract_type' => 'CDI', 'base_salary' => 250000, 'create_account' => false,
        ];
    }

    public function test_un_candidat_embauche_devient_un_employe(): void
    {
        $user = $this->makeUser();
        $app = $this->hiredApplication($user);

        $out = app(CandidateConversionService::class)->convert($app, $this->person());

        $this->assertNotEmpty($out['employee']->matricule);
        $this->assertSame($out['employee']->id, $app->candidate->fresh()->employee_id);
        $this->assertSame(1, Employee::count());
    }

    public function test_on_ne_convertit_pas_deux_fois(): void
    {
        $user = $this->makeUser();
        $app = $this->hiredApplication($user);
        $service = app(CandidateConversionService::class);

        $service->convert($app, $this->person());

        $this->expectException(ValidationException::class);
        $service->convert($app, $this->person());
    }

    public function test_une_candidature_non_embauchee_ne_se_convertit_pas(): void
    {
        $user = $this->makeUser();
        $app = $this->hiredApplication($user, 'offer');

        $this->expectException(ValidationException::class);
        app(CandidateConversionService::class)->convert($app, $this->person());

        $this->assertSame(0, Employee::count());
    }

    public function test_le_formulaire_cree_la_fiche_et_envoie_les_identifiants(): void
    {
        Mail::fake();
        $user = $this->makeUser();
        $app = $this->hiredApplication($user);

        Livewire::actingAs($user)->test(ConvertCandidate::class, ['application' => $app])
            ->set('gender', 'female')->set('birth_date', '1995-04-12')
            ->set('department_id', $this->department->id)->set('position_id', $this->position->id)
            ->set('contract_type', 'CDI')->set('base_salary', 250000)
            ->set('create_account', true)->set('login_email', 'awa.traore@acme.ml')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('result.mail', 'sent');

        Mail::assertSent(AccountCreated::class);
        $this->assertNotNull($app->candidate->fresh()->employee_id);
    }
}
