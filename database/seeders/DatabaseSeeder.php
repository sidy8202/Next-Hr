<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MatriculeSetting;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Données de démonstration : 5 entreprises, 5 départements et 25 postes
        // par entreprise, 5 chefs de département et 5 employés supplémentaires.
        // N'efface aucune donnée. Les mots de passe sont temporaires.

        $companies = [
            ['name' => 'Acme Mali', 'slug' => 'acme-mali', 'email' => 'contact@acme.ml'],
            ['name' => 'SahelTech Services', 'slug' => 'saheltech-services', 'email' => 'contact@saheltech.ml'],
            ['name' => 'Mandé Industries', 'slug' => 'mande-industries', 'email' => 'contact@mande-industries.ml'],
            ['name' => 'Bamako Logistics', 'slug' => 'bamako-logistics', 'email' => 'contact@bamako-logistics.ml'],
            ['name' => 'Sikasso Agro', 'slug' => 'sikasso-agro', 'email' => 'contact@sikasso-agro.ml'],
        ];

        $departments = [
            'DG' => ['name' => 'Direction Générale', 'positions' => [
                'Directeur Général', 'Directeur Général Adjoint', 'Assistant de Direction',
                'Chargé de Stratégie', 'Responsable Qualité',
            ]],
            'RH' => ['name' => 'Ressources Humaines', 'positions' => [
                'Directeur des Ressources Humaines', 'Responsable Recrutement', 'Gestionnaire de Paie',
                'Chargé de Formation', 'Assistant RH',
            ]],
            'FIN' => ['name' => 'Finance & Comptabilité', 'positions' => [
                'Directeur Financier', 'Chef Comptable', 'Comptable',
                'Contrôleur de Gestion', 'Assistant Comptable',
            ]],
            'IT' => ['name' => 'Informatique & Systèmes', 'positions' => [
                'Directeur des Systèmes d’Information', 'Administrateur Systèmes & Réseaux',
                'Développeur Logiciel', 'Technicien Support IT', 'Analyste Cybersécurité',
            ]],
            'OPS' => ['name' => 'Opérations & Logistique', 'positions' => [
                'Directeur des Opérations', 'Responsable Logistique', 'Superviseur Terrain',
                'Gestionnaire de Stock', 'Agent des Opérations',
            ]],
        ];

        $owners = [
            'acme-mali' => ['Sidy Lamine', 'owner@acme.ml'],
            'saheltech-services' => ['Aminata Coulibaly', 'owner@saheltech.ml'],
            'mande-industries' => ['Mamadou Diallo', 'owner@mande-industries.ml'],
            'bamako-logistics' => ['Fatoumata Traoré', 'owner@bamako-logistics.ml'],
            'sikasso-agro' => ['Ibrahim Konaté', 'owner@sikasso-agro.ml'],
        ];

        $staff = [
            ['Moussa', 'Diarra', 'moussa'],
            ['Awa', 'Traoré', 'awa'],
            ['Oumar', 'Keita', 'oumar'],
            ['Fatou', 'Sangaré', 'fatou'],
            ['Boubacar', 'Cissé', 'boubacar'],
        ];

        User::query()->updateOrCreate(
            ['email' => 'admin@nexthr.me'],
            [
                'name' => 'Admin Plateforme',
                'password' => Hash::make('ChangeMoi123!'),
                'is_platform_admin' => true,
                'is_company_owner' => false,
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        foreach ($companies as $data) {
            $company = Company::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'country' => 'ML',
                    'timezone' => 'Africa/Bamako',
                    'locale' => 'fr',
                    'currency' => 'XOF',
                    'status' => 'active',
                ]
            );

            MatriculeSetting::query()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'pattern' => '{PREFIX}{SEQ:4}',
                    'prefix' => strtoupper(substr(str_replace('-', '', $data['slug']), 0, 2)),
                    'counter_scope' => 'global',
                    'is_active' => true,
                ]
            );

            [$ownerName, $ownerEmail] = $owners[$data['slug']];
            User::query()->updateOrCreate(
                ['email' => $ownerEmail],
                [
                    'company_id' => $company->id,
                    'name' => $ownerName,
                    'password' => Hash::make('OwnerSecret123!'),
                    'is_platform_admin' => false,
                    'is_company_owner' => true,
                    'is_active' => true,
                    'must_change_password' => true,
                ]
            );

            $departmentModels = [];
            $positionModels = [];

            foreach ($departments as $code => $departmentData) {
                $department = Department::query()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $code],
                    ['name' => $departmentData['name'], 'is_active' => true]
                );
                $departmentModels[$code] = $department;

                foreach ($departmentData['positions'] as $title) {
                    $positionModels[$code][$title] = Position::query()->firstOrCreate(
                        ['company_id' => $company->id, 'department_id' => $department->id, 'title' => $title]
                    );
                }
            }

            // Cinq chefs de département, chacun avec un compte utilisateur.
            $heads = [
                ['Mariam', 'Koné'],
                ['Amadou', 'Diallo'],
                ['Kadiatou', 'Touré'],
                ['Issa', 'Traoré'],
                ['Adama', 'Sissoko'],
            ];

            $departmentCodes = array_keys($departments);

            foreach ($departmentCodes as $index => $code) {
                [$first, $last] = $heads[$index];
                $headPosition = $departments[$code]['positions'][0];
                $employee = Employee::query()->firstOrCreate(
                    ['company_id' => $company->id, 'first_name' => $first, 'last_name' => $last],
                    [
                        'matricule' => strtoupper(substr(str_replace('-', '', $data['slug']), 0, 2)) . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        'department_id' => $departmentModels[$code]->id,
                        'position_id' => $positionModels[$code][$headPosition]->id,
                        'hire_date' => '2022-01-01',
                    ]
                );

                $headEmail = strtolower($code) . '.chef@' . $data['slug'] . '.test';
                User::query()->updateOrCreate(
                    ['email' => $headEmail],
                    [
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'name' => $first . ' ' . $last,
                        'password' => Hash::make('StaffSecret123!'),
                        'is_platform_admin' => false,
                        'is_company_owner' => false,
                        'is_active' => true,
                        'must_change_password' => true,
                    ]
                );
            }

            // Au moins cinq comptes utilisateurs employés supplémentaires par entreprise.
            foreach ($staff as $index => [$first, $last, $prefix]) {
                $code = $departmentCodes[($index + 1) % count($departmentCodes)];
                $positionTitle = $departments[$code]['positions'][$index];
                $employee = Employee::query()->firstOrCreate(
                    ['company_id' => $company->id, 'first_name' => $first, 'last_name' => $last],
                    [
                        'matricule' => strtoupper(substr(str_replace('-', '', $data['slug']), 0, 2)) . str_pad((string) ($index + 101), 4, '0', STR_PAD_LEFT),
                        'department_id' => $departmentModels[$code]->id,
                        'position_id' => $positionModels[$code][$positionTitle]->id,
                        'hire_date' => '2023-03-01',
                    ]
                );

                $email = $prefix . '@' . $data['slug'] . '.test';
                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'name' => $first . ' ' . $last,
                        'password' => Hash::make('StaffSecret123!'),
                        'is_platform_admin' => false,
                        'is_company_owner' => false,
                        'is_active' => true,
                        'must_change_password' => true,
                    ]
                );

                Permission::query()->firstOrCreate(
                    ['user_id' => $user->id, 'resource' => 'employees'],
                    ['can_read' => true, 'can_update' => true, 'scope' => 'team']
                );
            }
        }
    }
}
