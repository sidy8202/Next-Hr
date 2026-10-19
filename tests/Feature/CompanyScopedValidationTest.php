<?php

namespace Tests\Feature;

use App\Livewire\Departments\DepartmentForm;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyScopedValidationTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_on_ne_peut_pas_designer_comme_chef_un_employe_d_une_autre_entreprise(): void
    {
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');
        $deptB = $this->makeDepartment($b, 'LOG', 'Logistique');
        $employeB = $this->makeEmployee($deptB, $this->makePosition($deptB), 'EM0001');

        Livewire::actingAs($this->makeUser($a, owner: true))
            ->test(DepartmentForm::class)
            ->set('form.name', 'Finance')->set('form.code', 'FIN')->set('form.head_id', $employeB->id)
            ->call('save')
            ->assertHasErrors(['form.head_id']);

        $this->assertSame(0, Department::withoutGlobalScopes()->where('code', 'FIN')->count());
    }

    public function test_le_meme_code_est_permis_dans_deux_entreprises_mais_pas_dans_la_meme(): void
    {
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');
        $this->makeDepartment($b, 'FIN', 'Finance de B');
        $owner = $this->makeUser($a, owner: true);

        Livewire::actingAs($owner)->test(DepartmentForm::class)
            ->set('form.name', 'Finance A')->set('form.code', 'FIN')->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($owner)->test(DepartmentForm::class)
            ->set('form.name', 'Doublon')->set('form.code', 'FIN')->call('save')
            ->assertHasErrors(['form.code']);
    }

    public function test_sans_droit_de_creation_la_page_est_interdite(): void
    {
        $user = $this->makeUser($this->makeCompany('a'));
        Permission::create(['user_id' => $user->id, 'resource' => 'departments', 'can_read' => true, 'scope' => 'all']);

        Livewire::actingAs($user)->test(DepartmentForm::class)->assertForbidden();
    }
}
