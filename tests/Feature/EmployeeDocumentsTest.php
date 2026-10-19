<?php

namespace Tests\Feature;

use App\Livewire\Employees\EmployeeForm;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class EmployeeDocumentsTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_une_piece_jointe_est_rangee_dans_le_dossier_prive(): void
    {
        Storage::fake('local');
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(EmployeeForm::class)
            ->set('form.first_name', 'Awa')->set('form.last_name', 'Traoré')
            ->set('form.department_id', $dept->id)->set('form.position_id', $pos->id)
            ->set('form.hire_date', '2026-11-03')->set('form.contract_type', 'CDI')->set('form.base_salary', 350000)
            ->set('docs.0.type', 'diploma')->set('docs.0.file', UploadedFile::fake()->create('diplome.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertStringStartsWith("companies/{$company->id}/employees/", $doc->file_path);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_un_fichier_non_autorise_est_refuse(): void
    {
        Storage::fake('local');
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $pos = $this->makePosition($dept);

        Livewire::actingAs($this->makeUser($company, owner: true))
            ->test(EmployeeForm::class)
            ->set('form.first_name', 'Awa')->set('form.last_name', 'Traoré')
            ->set('form.department_id', $dept->id)->set('form.position_id', $pos->id)
            ->set('form.hire_date', '2026-11-03')->set('form.contract_type', 'CDI')->set('form.base_salary', 350000)
            ->set('docs.0.type', 'diploma')->set('docs.0.file', UploadedFile::fake()->create('virus.exe', 10))
            ->call('save')
            ->assertHasErrors(['docs.0.file']);

        $this->assertSame(0, Document::count());
    }

    public function test_le_telechargement_exige_le_droit_de_lecture(): void
    {
        Storage::fake('local');
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $employee = $this->makeEmployee($dept, $this->makePosition($dept));

        Storage::disk('local')->put('companies/1/x.pdf', 'contenu');
        $doc = Document::forceCreate([
            'company_id' => $company->id, 'employee_id' => $employee->id, 'type' => 'diploma',
            'title' => 'Diplôme', 'file_path' => 'companies/1/x.pdf',
        ]);

        $this->actingAs($this->makeUser($company))
            ->get(route('employees.documents.download', [$employee->id, $doc->id]))
            ->assertForbidden();

        $this->actingAs($this->makeUser($company, owner: true))
            ->get(route('employees.documents.download', [$employee->id, $doc->id]))
            ->assertOk();
    }

    public function test_un_document_d_une_autre_entreprise_est_introuvable(): void
    {
        Storage::fake('local');
        $a = $this->makeCompany('a');
        $b = $this->makeCompany('b');
        $deptB = $this->makeDepartment($b, 'LOG', 'Logistique');
        $employeeB = $this->makeEmployee($deptB, $this->makePosition($deptB));

        Storage::disk('local')->put('companies/2/x.pdf', 'contenu');
        $doc = Document::forceCreate([
            'company_id' => $b->id, 'employee_id' => $employeeB->id, 'type' => 'diploma', 'title' => 'D', 'file_path' => 'companies/2/x.pdf',
        ]);

        $this->actingAs($this->makeUser($a, owner: true))
            ->get(route('employees.documents.download', [$employeeB->id, $doc->id]))
            ->assertNotFound();
    }
}
