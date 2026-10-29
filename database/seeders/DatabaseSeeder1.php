<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin Plateforme
        User::forceCreate([
            'name'                 => 'Admin Plateforme',
            'email'                => 'admin@nexthr.me',
            'password'             => Hash::make('ChangeMoi123!'),
            'is_platform_admin'    => true,
            'is_company_owner'     => false,
            'is_active'            => true,
            'must_change_password' => false,
        ]);

        // 2. Entreprise Acme Mali
        $acme = Company::create([
            'name'     => 'Acme Mali',
            'slug'     => 'acme-mali',
            'email'    => 'contact@acme.ml',
            'status'   => 'active',
            'currency' => 'XOF',
        ]);

        //Matricule
        \App\Models\MatriculeSetting::forceCreate([
        'company_id'    => $acme->id,
        'pattern'       => '{PREFIX}{SEQ:4}',
        'prefix'        => 'EM',
        'counter_scope' => 'global',
        'is_active'     => true,
        ]);

        // 3. Propriétaire
        User::forceCreate([
            'company_id'           => $acme->id,
            'name'                 => 'Sidy Lamine',
            'email'                => 'owner@acme.ml',
            'password'             => Hash::make('OwnerSecret123!'),
            'is_platform_admin'    => false,
            'is_company_owner'     => true,
            'is_active'            => true,
            'must_change_password' => true,
        ]);

        // 4. Département Finance
        $finance = Department::forceCreate([
            'company_id' => $acme->id,
            'name'       => 'Finance & Comptabilité',
            'code'       => 'FIN',
            'is_active'  => true,
        ]);

        $posManager = Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $finance->id,
            'title'         => 'Directeur Financier',
        ]);

        $posAccountant = Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $finance->id,
            'title'         => 'Comptable',
        ]);

        // 5. Employés
        $moussa = Employee::forceCreate([
            'company_id'    => $acme->id,
            'matricule'     => 'EM0001',
            'first_name'    => 'Moussa',
            'last_name'     => 'Diarra',
            'department_id' => $finance->id,
            'position_id'   => $posManager->id,
            'hire_date'     => '2026-01-01',
        ]);

        $awa = Employee::forceCreate([
            'company_id'    => $acme->id,
            'matricule'     => 'EM0002',
            'first_name'    => 'Awa',
            'last_name'     => 'Traoré',
            'department_id' => $finance->id,
            'position_id'   => $posAccountant->id,
            'manager_id'    => $moussa->id,
            'hire_date'     => '2026-01-01',
        ]);

        $oumar = Employee::forceCreate([
            'company_id'    => $acme->id,
            'matricule'     => 'EM0003',
            'first_name'    => 'Oumar',
            'last_name'     => 'Keita',
            'department_id' => $finance->id,
            'position_id'   => $posAccountant->id,
            'manager_id'    => $moussa->id,
            'hire_date'     => '2026-01-01',
        ]);

        // 6. Utilisateurs associés
        $uMoussa = User::forceCreate([
            'company_id'           => $acme->id,
            'employee_id'          => $moussa->id,
            'name'                 => 'Moussa Diarra',
            'email'                => 'moussa@acme.ml',
            'password'             => Hash::make('Secret123!'),
            'must_change_password' => true,
        ]);

        $uAwa = User::forceCreate([
            'company_id'           => $acme->id,
            'employee_id'          => $awa->id,
            'name'                 => 'Awa Traoré',
            'email'                => 'awa@acme.ml',
            'password'             => Hash::make('Secret123!'),
            'must_change_password' => true,
        ]);

        // 7. Permissions de test
        Permission::create([
            'user_id'    => $uMoussa->id,
            'resource'   => 'employees',
            'can_read'   => true,
            'can_update' => true,
            'scope'      => 'team',
        ]);

        Permission::create([
            'user_id'  => $uAwa->id,
            'resource' => 'employees',
            'can_read' => true,
            'scope'    => 'own',
        ]);


    }
}
