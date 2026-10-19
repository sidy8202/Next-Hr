<?php

namespace Tests\Feature;

use App\Mail\AccountCreated;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class EmployeeFlowTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private function person(int $deptId, int $posId, string $first, string $hire, array $extra = []): array
    {
        return $extra + [
            'first_name' => $first, 'last_name' => 'Test', 'department_id' => $deptId, 'position_id' => $posId,
            'hire_date' => $hire, 'contract_type' => 'CDD', 'duration_months' => 6, 'base_salary' => 300000,
        ];
    }

    public function test_chaque_recrue_garde_sa_propre_date_de_debut(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        $result = app(EmployeeService::class)->createMany([
            $this->person($dept->id, $pos->id, 'Awa', '2026-11-03'),
            $this->person($dept->id, $pos->id, 'Oumar', '2026-12-01', ['contract_type' => 'CDI']),
        ]);

        $this->assertSame(['EM0001', 'EM0002'], collect($result['employees'])->pluck('matricule')->all());
        $this->assertSame('2026-11-03', Contract::where('employee_id', $result['employees'][0]->id)->first()->start_date->toDateString());
        $this->assertSame('2026-12-01', Contract::where('employee_id', $result['employees'][1]->id)->first()->start_date->toDateString());
        $this->assertSame('2027-05-02', Contract::where('employee_id', $result['employees'][0]->id)->first()->end_date->toDateString());
        $this->assertNull(Contract::where('employee_id', $result['employees'][1]->id)->first()->end_date);
    }

    public function test_le_generateur_saute_les_matricules_deja_pris(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        $this->makeEmployee($dept, $pos, 'EM0001'); // créé « à la main », hors générateur
        $this->makeEmployee($dept, $pos, 'EM0002', 'Moussa', 'Diarra');

        $result = app(EmployeeService::class)->createMany([$this->person($dept->id, $pos->id, 'Awa', '2026-11-03')]);

        $this->assertSame('EM0003', $result['employees'][0]->matricule);
    }

    public function test_une_erreur_au_milieu_du_lot_n_enregistre_rien_et_ne_perd_aucun_numero(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        try {
            app(EmployeeService::class)->createMany([
                $this->person($dept->id, $pos->id, 'Awa', '2026-11-03'),
                $this->person($dept->id, 999999, 'Oumar', '2026-11-03'), // poste inexistant : clé étrangère
            ]);
            $this->fail('Une exception était attendue.');
        } catch (QueryException) {
            // attendu
        }

        $this->assertSame(0, Employee::count());
        $this->assertSame(0, Contract::count());

        $ok = app(EmployeeService::class)->createMany([$this->person($dept->id, $pos->id, 'Awa', '2026-11-03')]);
        $this->assertSame('EM0001', $ok['employees'][0]->matricule); // le compteur est revenu en arrière
    }

    public function test_creation_d_un_compte_avec_famille_et_deux_emails(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        $result = app(EmployeeService::class)->createMany([$this->person($dept->id, $pos->id, 'Awa', '2026-11-03', [
            'marital_status' => 'married', 'spouse_works' => true, 'children_count' => 2,
            'work_email' => 'awa@acme.ml', 'personal_email' => 'awa@gmail.com',
            'create_account' => true, 'login_email' => 'awa@acme.ml', 'send_to' => ['awa@acme.ml', 'awa@gmail.com'],
        ])]);

        $employee = $result['employees'][0]->fresh();
        $this->assertSame(2, $employee->children_count);
        $this->assertTrue((bool) $employee->spouse_works);

        $user = User::where('employee_id', $employee->id)->first();
        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertNotSame($result['credentials'][0]['password'], $user->password); // jamais en clair
    }

    public function test_les_identifiants_sont_envoyes_aux_deux_adresses(): void
    {
        Mail::fake();
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        $status = app(EmployeeService::class)->sendCredentials([[
            'matricule' => 'EM0001', 'name' => 'Awa Test', 'login_email' => 'awa@acme.ml', 'password' => 'abc123XYZ789',
            'send_to' => ['awa@acme.ml', 'awa@gmail.com'],
        ]]);

        $this->assertSame('sent', $status['EM0001']);
        Mail::assertSent(AccountCreated::class, fn ($m) => $m->hasTo('awa@acme.ml') && $m->hasTo('awa@gmail.com'));
    }

    public function test_sans_destinataire_rien_n_est_envoye(): void
    {
        Mail::fake();
        $this->actingAs($this->makeUser($this->makeCompany(), owner: true));

        $status = app(EmployeeService::class)->sendCredentials([[
            'matricule' => 'EM0001', 'name' => 'Awa', 'login_email' => 'awa@acme.ml', 'password' => 'x', 'send_to' => [],
        ]]);

        $this->assertSame('none', $status['EM0001']);
        Mail::assertNothingSent();
    }
}
