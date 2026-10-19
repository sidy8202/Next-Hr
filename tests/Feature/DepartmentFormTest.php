<?php

namespace Tests\Feature;

use App\Livewire\Departments\DepartmentForm;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class DepartmentFormTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_en_modification_un_poste_est_enregistre_immediatement(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(DepartmentForm::class, ['department' => $dept])
            ->set('newPosition', 'Comptable')->call('addPosition')->assertHasNoErrors();

        $this->assertSame(1, Position::where('department_id', $dept->id)->count());
    }

    public function test_un_poste_en_double_est_refuse_avec_un_message(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $this->makePosition($dept, 'Comptable');

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(DepartmentForm::class, ['department' => $dept])
            ->set('newPosition', 'comptable')->call('addPosition')->assertHasErrors('newPosition');

        $this->assertSame(1, Position::where('department_id', $dept->id)->count());
    }

    public function test_un_poste_occupe_ne_se_supprime_pas(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);
        $this->makeEmployee($dept, $pos);

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(DepartmentForm::class, ['department' => $dept])
            ->call('deletePosition', $pos->id);

        $this->assertNotNull(Position::find($pos->id));
    }

    public function test_en_creation_les_postes_sont_crees_avec_le_departement(): void
    {
        $company = $this->makeCompany();

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(DepartmentForm::class)
            ->set('form.name', 'Finance')->set('form.code', 'FIN')
            ->set('newPosition', 'Comptable')->call('addPosition')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(1, Position::count());
    }
}
