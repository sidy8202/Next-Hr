<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Administrateur de la plateforme (Aucune entreprise attribuée)
        User::forceCreate([
            'name'                 => 'Admin Plateforme',
            'email'                => 'admin@nexthr.me',
            'password'             => Hash::make('ChangeMoi123!'),
            'is_platform_admin'    => true,
            'is_company_owner'     => false,
            'is_active'            => true,
            'must_change_password' => false,
        ]);

        // 2. Entreprise de démonstration
        $acme = Company::create([
            'name'     => 'Acme Mali',
            'slug'     => 'acme-mali',
            'email'    => 'contact@acme.ml',
            'phone'    => '+223 20 00 00 00',
            'country'  => 'ML',
            'currency' => 'XOF',
            'status'   => 'active',
        ]);

        // 3. Propriétaire de l'entreprise Acme Mali
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

        // 4. Départements d'Acme Mali
        $finance = Department::forceCreate([
            'company_id' => $acme->id,
            'name'       => 'Finance & Comptabilité',
            'code'       => 'FIN',
            'is_active'  => true,
        ]);

        $it = Department::forceCreate([
            'company_id' => $acme->id,
            'name'       => 'Informatique & Systèmes',
            'code'       => 'IT',
            'is_active'  => true,
        ]);

        $hr = Department::forceCreate([
            'company_id' => $acme->id,
            'name'       => 'Ressources Humaines',
            'code'       => 'RH',
            'is_active'  => true,
        ]);

        // 5. Postes par département

        // -- Département Finance
        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $finance->id,
            'title'         => 'Directeur Financier',
            'grade'         => 'Cadre Supérieur',
            'salary_min'    => 1000000,
            'salary_max'    => 2000000,
        ]);

        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $finance->id,
            'title'         => 'Comptable Principal',
            'grade'         => 'Cadre',
            'salary_min'    => 500000,
            'salary_max'    => 900000,
        ]);

        // -- Département Informatique
        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $it->id,
            'title'         => 'Administrateur Systèmes & Réseaux',
            'grade'         => 'Cadre',
            'salary_min'    => 600000,
            'salary_max'    => 1200000,
        ]);

        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $it->id,
            'title'         => 'Développeur Web Fullstack',
            'grade'         => 'Cadre',
            'salary_min'    => 500000,
            'salary_max'    => 1000000,
        ]);

        // -- Département Ressources Humaines
        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $hr->id,
            'title'         => 'Responsable RH',
            'grade'         => 'Cadre Supérieur',
            'salary_min'    => 800000,
            'salary_max'    => 1500000,
        ]);

        Position::forceCreate([
            'company_id'    => $acme->id,
            'department_id' => $hr->id,
            'title'         => 'Gestionnaire de la Paie',
            'grade'         => 'Agent de Maîtrise',
            'salary_min'    => 400000,
            'salary_max'    => 750000,
        ]);
    }
}
