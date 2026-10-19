<?php

namespace Tests\Feature;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_sans_droit_de_lecture_la_page_des_departements_est_interdite(): void
    {
        $this->withoutVite();
        $company = $this->makeCompany();

        $this->actingAs($this->makeUser($company))
            ->get(route('departments.index'))
            ->assertForbidden();
    }

    public function test_avec_le_droit_de_lecture_la_page_s_affiche(): void
    {
        $this->withoutVite();
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        Permission::create(['user_id' => $user->id, 'resource' => 'departments', 'can_read' => true, 'scope' => 'all']);

        $this->actingAs($user)->get(route('departments.index'))->assertOk();
    }

    public function test_les_portees_own_et_team(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        $chef = $this->makeEmployee($dept, $pos, 'EM0001', 'Moussa', 'Diarra');
        $awa = $this->makeEmployee($dept, $pos, 'EM0002', 'Awa', 'Traoré', $chef->id);
        $oumar = $this->makeEmployee($dept, $pos, 'EM0003', 'Oumar', 'Keita', $chef->id);

        $uChef = $this->makeUser($company, employee: $chef);
        $uAwa = $this->makeUser($company, employee: $awa);
        Permission::create(['user_id' => $uChef->id, 'resource' => 'employees', 'can_read' => true, 'can_update' => true, 'scope' => 'team']);
        Permission::create(['user_id' => $uAwa->id, 'resource' => 'employees', 'can_read' => true, 'scope' => 'own']);

        $this->assertTrue($uAwa->canAccessRecord('read', 'employees', $awa));
        $this->assertFalse($uAwa->canAccessRecord('read', 'employees', $oumar));

        $this->assertTrue($uChef->canAccessRecord('read', 'employees', $awa));
        $this->assertTrue($uChef->canAccessRecord('update', 'employees', $oumar));
        $this->assertFalse($uChef->canAccessRecord('delete', 'employees', $oumar));
    }

    public function test_la_visibilite_des_employes_suit_la_portee(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        $chef = $this->makeEmployee($dept, $pos, 'EM0001', 'Moussa', 'Diarra');
        $awa = $this->makeEmployee($dept, $pos, 'EM0002', 'Awa', 'Traoré', $chef->id);
        $this->makeEmployee($dept, $pos, 'EM0003', 'Oumar', 'Keita');

        $uAwa = $this->makeUser($company, employee: $awa);
        Permission::create(['user_id' => $uAwa->id, 'resource' => 'employees', 'can_read' => true, 'scope' => 'own']);
        $this->actingAs($uAwa);

        $this->assertSame(1, \App\Models\Employee::visibleTo($uAwa)->count());
    }

    public function test_une_ressource_inconnue_leve_une_exception(): void
    {
        $user = $this->makeUser($this->makeCompany());

        $this->expectException(InvalidArgumentException::class);
        $user->canDo('read', 'employes'); // faute de frappe
    }

    public function test_l_administrateur_de_la_plateforme_n_a_aucun_acces_aux_donnees_rh(): void
    {
        $admin = \App\Models\User::forceCreate([
            'name' => 'Admin', 'email' => 'admin@test.ml', 'password' => 'secret123',
            'is_platform_admin' => true, 'is_active' => true, 'must_change_password' => false,
        ]);

        $this->assertFalse($admin->canDo('read', 'employees'));
    }
}
