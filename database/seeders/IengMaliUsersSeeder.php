<?php
namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class IengMaliUsersSeeder extends Seeder
{
    public function run(): void
    {
        $dryRun = filter_var(
            env('IENG_USERS_DRY_RUN', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $company = Company::where('slug', 'ieng-mali')->firstOrFail();

        $employees = Employee::where('company_id', $company->id)
            ->orderBy('id')
            ->get();

        $resources = config('permissions.resources', []);
        $templates = config('permission_meta.templates', []);
        $employeeScoped = config('permission_meta.employee_scoped', []);

        foreach (['employee', 'hr_agent', 'recruiter'] as $required) {
            if (!isset($templates[$required]['permissions'])) {
                throw new RuntimeException(
                    "Modèle de permissions manquant : {$required}"
                );
            }
        }

        $existingEmails = User::pluck('email')
            ->map(fn ($email) => strtolower($email))
            ->flip()
            ->all();

        $seenEmployeeIds = [];
        $credentials = [];
        $errors = [];

        $stats = [
            'employees' => $employees->count(),
            'created' => 0,
            'already_linked' => 0,
            'permissions' => 0,
            'hr_agent' => 0,
            'recruiter' => 0,
        ];

        $normalizeName = function (string $name): string {
            return Str::slug(Str::ascii($name));
        };

        $makeEmail = function (Employee $employee) use (
            &$existingEmails
        ): string {
            $fullName = trim(
                $employee->first_name . ' ' . $employee->last_name
            );

            $base = Str::slug(Str::ascii($fullName), '.');

            if ($base === '') {
                throw new RuntimeException(
                    "Nom inexploitable pour l'employé ID {$employee->id}"
                );
            }

            $email = $base . '@ieng-mali.com';
            $suffix = 2;

            while (isset($existingEmails[strtolower($email)])) {
                $email = $base . $suffix . '@ieng-mali.com';
                $suffix++;
            }

            $existingEmails[strtolower($email)] = true;

            return $email;
        };

        $getTemplate = function (Employee $employee) use (
            $normalizeName
        ): string {
            $fullName = trim(
                $employee->first_name . ' ' . $employee->last_name
            );

            $name = $normalizeName($fullName);

            if ($name === 'djouncounda-sissoko') {
                return 'hr_agent';
            }

            if ($name === 'leger-marie-stanislas-traore') {
                return 'recruiter';
            }

            return 'employee';
        };

        $makePermissions = function (User $user, string $template) use (
            $templates,
            $resources,
            $employeeScoped
        ): int {
            $permissions = $templates[$template]['permissions'];
            $count = 0;

            foreach ($permissions as $resource => [$letters, $scope]) {
                // Même comportement que AccessMatrix :
                // ignorer les ressources absentes de permissions.php.
                if (!array_key_exists($resource, $resources)) {
                    continue;
                }

                $allowedScopes = in_array(
                    $resource,
                    $employeeScoped,
                    true
                ) ? ['own', 'team', 'all'] : ['all'];

                if (!in_array($scope, $allowedScopes, true)) {
                    $scope = $allowedScopes[0];
                }

                Permission::forceCreate([
                    'user_id' => $user->id,
                    'resource' => $resource,
                    'can_create' => str_contains($letters, 'c'),
                    'can_read' => str_contains($letters, 'r'),
                    'can_update' => str_contains($letters, 'u'),
                    'can_delete' => str_contains($letters, 'd'),
                    'scope' => $scope,
                ]);

                $count++;
            }

            return $count;
        };

        DB::beginTransaction();

        try {
            foreach ($employees as $employee) {
                try {
                    $existingUser = User::where(
                        'company_id',
                        $company->id
                    )->where('employee_id', $employee->id)->first();

                    if ($existingUser) {
                        $stats['already_linked']++;
                        continue;
                    }

                    if (isset($seenEmployeeIds[$employee->id])) {
                        throw new RuntimeException(
                            "Employé déjà traité : {$employee->id}"
                        );
                    }

                    $seenEmployeeIds[$employee->id] = true;

                    $template = $getTemplate($employee);
                    $email = $makeEmail($employee);

                    $fullName = trim(
                        $employee->first_name . ' ' . $employee->last_name
                    );

                    // Mot de passe aléatoire individuel.
                    $password = Str::password(16);

                    if (!$dryRun) {
                        $user = User::forceCreate([
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'name' => $fullName,
                            'email' => $email,
                            'password' => $password,
                            'is_platform_admin' => false,
                            'is_company_owner' => false,
                            'is_active' => true,
                            'must_change_password' => true,
                        ]);

                        $stats['permissions'] += $makePermissions(
                            $user,
                            $template
                        );

                        $credentials[] = [
                            'employee' => $fullName,
                            'email' => $email,
                            'temporary_password' => $password,
                            'template' => $template,
                        ];
                    }

                     else {
                        $stats['permissions'] += collect(
                            $templates[$template]['permissions']
                        )->filter(
                            fn ($value, $resource) => array_key_exists($resource, $resources)
                        )->count();
                    }

                    $stats['created']++;

                    if ($template === 'hr_agent') {
                        $stats['hr_agent']++;
                    }

                    if ($template === 'recruiter') {
                        $stats['recruiter']++;
                    }

                } catch (Throwable $e) {
                    $errors[] = [
                        'employee_id' => $employee->id,
                        'name' => $employee->fullName,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if ($dryRun || count($errors) > 0) {
                DB::rollBack();
            } else {
                DB::commit();
            }

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->command->newLine();
        $this->command->info(
            $dryRun
                ? 'SIMULATION — aucune modification conservée.'
                : (
                    count($errors) > 0
                        ? 'IMPORT ANNULÉ — erreurs détectées.'
                        : 'IMPORT TERMINÉ.'
                )
        );

        $this->command->table(
            ['Indicateur', 'Résultat'],
            [
                ['Employés examinés', $stats['employees']],
                ['Comptes à créer / créés', $stats['created']],
                ['Comptes déjà liés', $stats['already_linked']],
                ['Permissions préparées / créées', $stats['permissions']],
                ['Comptes HR Agent', $stats['hr_agent']],
                ['Comptes Recruteur', $stats['recruiter']],
                ['Erreurs', count($errors)],
            ]
        );

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->command->error(
                    "Employé {$error['employee_id']} ({$error['name']}) : {$error['error']}"
                );
            }

            return;
        }

        if ($dryRun) {
            $this->command->warn(
                'Simulation terminée. La transaction a été annulée.'
            );

            return;
        }

        // Export privé des identifiants temporaires.
        $directory = storage_path('app/private');

        File::ensureDirectoryExists($directory);

        $file = $directory . '/ieng-mali-credentials-' .
            now()->format('Ymd-His') . '.csv';

        $handle = fopen($file, 'w');

        if ($handle === false) {
            throw new RuntimeException(
                'Impossible de créer le fichier privé des identifiants.'
            );
        }

        // BOM UTF-8 pour faciliter l'ouverture dans Excel.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv(
            $handle,
            ['Employé', 'Adresse e-mail', 'Mot de passe temporaire', 'Modèle'],
            ';'
        );

        foreach ($credentials as $credential) {
            fputcsv(
                $handle,
                [
                    $credential['employee'],
                    $credential['email'],
                    $credential['temporary_password'],
                    $credential['template'],
                ],
                ';'
            );
        }

        fclose($handle);

        $this->command->info(
            'Fichier privé des identifiants : ' . $file
        );
    }
}
