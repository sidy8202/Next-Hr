<?php

namespace Tests\Feature\Concerns;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeService;



/**
 * Données de test des congés. ADAPTER si besoin : colonnes obligatoires des départements et postes
 * (copiez la façon de les créer dans DepartmentServiceTest) ; la création des employés passe par votre EmployeeService.
 */
trait LeaveFixtures
{
    use RecruitmentFixtures;

    protected Department $dept;

    protected Position $position;

    /** Crée une entreprise, son propriétaire (= la RH : tous les droits) et un département avec un poste. */
    protected function makeCompanyContext(): User
    {
        $hr = $this->makeUser();
        $this->actingAs($hr);

        $this->dept = Department::create(['name' => 'Informatique', 'code' => 'IT']);
        $this->position = Position::create(['department_id' => $this->dept->id, 'title' => 'Technicien']);

        return $hr;
    }

    protected function makeEmployee(array $over = []): Employee
    {
        static $n = 0;
        $n++;

        $result = app(EmployeeService::class)->createMany([array_merge([
            'first_name' => 'Emp'.$n, 'last_name' => 'Test'.$n, 'gender' => 'M', 'birth_date' => '1990-01-01',
            'marital_status' => 'single', 'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'hire_date' => today()->toDateString(), 'contract_type' => 'CDI', 'base_salary' => 200000, 'create_account' => false,
        ], $over)]);

        return $result['employees'][0]->fresh();
    }

    protected function makeAccount(Employee $employee): User
    {
        return User::forceCreate([
            'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'name' => $employee->full_name,
            'email' => "emp{$employee->id}@example.com", 'password' => 'secret-pass-123',
            'is_active' => true, 'must_change_password' => false,
        ]);
    }
}
