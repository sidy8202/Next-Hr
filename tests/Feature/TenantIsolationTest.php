<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_un_utilisateur_ne_voit_jamais_les_departements_d_une_autre_entreprise(): void
    {
        $this->withoutVite();
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');

        $this->makeDepartment($a, 'FIN', 'Finance');
        $autre = $this->makeDepartment($b, 'LOG', 'Logistique');

        $this->actingAs($this->makeUser($a, owner: true));

        $this->assertSame(1, Department::count());
        $this->get(route('departments.show', $autre->id))->assertNotFound();
    }

    public function test_les_employes_d_une_autre_entreprise_sont_invisibles(): void
    {
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');

        $deptA = $this->makeDepartment($a);
        $deptB = $this->makeDepartment($b, 'LOG', 'Logistique');
        $this->makeEmployee($deptA, $this->makePosition($deptA), 'EM0001');
        $autre = $this->makeEmployee($deptB, $this->makePosition($deptB), 'EM0001', 'Moussa', 'Diarra');

        $this->actingAs($this->makeUser($a, owner: true));

        $this->assertSame(1, Employee::count());
        $this->assertNull(Employee::find($autre->id));
    }

    public function test_la_creation_remplit_automatiquement_l_entreprise_de_l_utilisateur(): void
    {
        $a = $this->makeCompany('a');
        $this->actingAs($this->makeUser($a, owner: true));

        $department = Department::create(['name' => 'RH', 'code' => 'RH']);

        $this->assertSame($a->id, $department->company_id);
    }
}
