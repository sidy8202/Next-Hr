<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Services\DepartmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_un_enregistrement_en_erreur_ne_laisse_aucun_departement(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        try {
            // head_id inexistant : la clé étrangère échoue APRÈS la création du département → rollback complet
            app(DepartmentService::class)->save(null, ['name' => 'Test', 'code' => 'TST', 'head_id' => 999999, 'positions' => ['Comptable']]);
            $this->fail('Une exception était attendue.');
        } catch (QueryException) {
            // attendu
        }

        $this->assertSame(0, Department::count());
        $this->assertSame(0, \App\Models\Position::count());
    }

    public function test_creation_avec_chef_membres_et_postes(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        $autre = $this->makeDepartment($company, 'IT', 'Informatique');
        $pos = $this->makePosition($autre);
        $chef = $this->makeEmployee($autre, $pos, 'EM0001', 'Moussa', 'Diarra');
        $membre = $this->makeEmployee($autre, $pos, 'EM0002', 'Awa', 'Traoré');

        $finance = app(DepartmentService::class)->save(null, [
            'name' => 'Finance', 'code' => 'fin', 'is_active' => true,
            'head_id' => $chef->id, 'member_ids' => [$membre->id], 'positions' => ['Comptable', 'Chef comptable'],
        ]);

        $this->assertSame('FIN', $finance->code);
        $this->assertSame($chef->id, $finance->head_id);
        $this->assertSame($finance->id, Employee::find($membre->id)->department_id);
        $this->assertSame($chef->id, Employee::find($membre->id)->manager_id);
        $this->assertSame(2, $finance->positions()->count());
    }

    public function test_le_chef_n_est_chef_que_d_un_seul_departement(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        $a = $this->makeDepartment($company, 'IT', 'Informatique');
        $chef = $this->makeEmployee($a, $this->makePosition($a), 'EM0001');
        $a->update(['head_id' => $chef->id]);

        $b = app(DepartmentService::class)->save(null, ['name' => 'Finance', 'code' => 'FIN', 'head_id' => $chef->id]);

        $this->assertNull($a->fresh()->head_id);
        $this->assertSame($chef->id, $b->head_id);
    }

    public function test_un_departement_avec_employes_ne_se_supprime_pas(): void
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        $dept = $this->makeDepartment($company);
        $this->makeEmployee($dept, $this->makePosition($dept));

        $this->assertNotNull(app(DepartmentService::class)->deletionBlocker($dept));
    }
}
