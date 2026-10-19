<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;

/** Fabriques de données de test : toujours avec company_id explicite et indicateurs complets. */
trait CreatesTenants
{
    protected function makeCompany(string $slug = 'acme'): Company
    {
        return Company::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => 'active']);
    }

    protected function makeUser(Company $company, bool $owner = false, ?Employee $employee = null): User
    {
        return User::forceCreate([
            'company_id' => $company->id,
            'employee_id' => $employee?->id,
            'name' => 'Utilisateur '.uniqid(),
            'email' => uniqid('u').'@test.ml',
            'password' => 'secret123',
            'is_company_owner' => $owner,
            'is_active' => true,               // explicite : forceCreate ne relit pas les valeurs par défaut de la base
            'must_change_password' => false,
        ]);
    }

    protected function makeDepartment(Company $company, string $code = 'FIN', string $name = 'Finance'): Department
    {
        return Department::forceCreate(['company_id' => $company->id, 'code' => $code, 'name' => $name, 'is_active' => true]);
    }

    protected function makePosition(Department $department, string $title = 'Comptable'): Position
    {
        return Position::forceCreate(['company_id' => $department->company_id, 'department_id' => $department->id, 'title' => $title]);
    }

    protected function makeEmployee(Department $department, Position $position, string $matricule = 'EM0001', string $first = 'Awa', string $last = 'Traoré', ?int $managerId = null): Employee
    {
        return Employee::forceCreate([
            'company_id' => $department->company_id,
            'matricule' => $matricule,
            'first_name' => $first,
            'last_name' => $last,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $managerId,
            'hire_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }
}
