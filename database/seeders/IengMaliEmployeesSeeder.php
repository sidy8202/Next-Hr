<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\MatriculeGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class IengMaliEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $dryRun = filter_var(
            getenv('IENG_SEED_DRY_RUN') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );

        $path = database_path(
            'seeders/data/ieng_mali_employees.csv'
        );

        if (! is_file($path)) {
            throw new RuntimeException(
                "Fichier CSV introuvable : {$path}"
            );
        }

        $rows = $this->readCsv($path);

        if (count($rows) === 0) {
            throw new RuntimeException(
                'Le fichier CSV ne contient aucune ligne exploitable.'
            );
        }

        $created = [
            'company' => false,
            'departments' => 0,
            'positions' => 0,
            'employees' => 0,
        ];

        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            // Retrouver ou créer uniquement IENG Mali.
            $company = Company::withoutGlobalScopes()
                ->where('slug', 'ieng-mali')
                ->first();

            if (! $company) {
                $company = Company::forceCreate([
                    'name' => 'IENG Mali',
                    'slug' => 'ieng-mali',
                    'country' => 'ML',
                    'timezone' => 'Africa/Bamako',
                    'locale' => 'fr',
                    'currency' => 'XOF',
                    'status' => 'active',
                ]);

                $created['company'] = true;
            }

            $generator = app(MatriculeGenerator::class);

            foreach ($rows as $index => $row) {
                $line = $index + 2;

                $firstName = trim($row['first_name']);
                $lastName = trim($row['last_name']);
                $departmentName = trim($row['department']);
                $positionTitle = trim($row['position']);

                if (
                    $firstName === '' ||
                    $lastName === '' ||
                    $departmentName === '' ||
                    $positionTitle === ''
                ) {
                    $errors[] = "Ligne {$line} : champ obligatoire vide.";
                    continue;
                }

                // Rechercher le département uniquement chez IENG Mali.
                $department = Department::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [
                        mb_strtolower($departmentName),
                    ])
                    ->first();

                if (! $department) {
                    $baseCode = Str::upper(
                        Str::substr(
                            Str::slug($departmentName, ''),
                            0,
                            10
                        )
                    );

                    $baseCode = $baseCode !== ''
                        ? $baseCode
                        : 'DEP';

                    $code = $baseCode;
                    $suffix = 2;

                    while (
                        DB::table('departments')
                            ->where('company_id', $company->id)
                            ->where('code', $code)
                            ->exists()
                    ) {
                        $code = Str::substr(
                            $baseCode,
                            0,
                            20 - strlen((string) $suffix)
                        ) . $suffix++;

                    }

                    $department = Department::forceCreate([
                        'company_id' => $company->id,
                        'name' => $departmentName,
                        'code' => $code,
                        'is_active' => true,
                    ]);

                    $created['departments']++;
                }

                // Rechercher le poste dans le département correspondant.
                $position = Position::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('department_id', $department->id)
                    ->whereRaw('LOWER(TRIM(title)) = ?', [
                        mb_strtolower($positionTitle),
                    ])
                    ->first();

                if (! $position) {
                    $position = Position::forceCreate([
                        'company_id' => $company->id,
                        'department_id' => $department->id,
                        'title' => $positionTitle,
                    ]);

                    $created['positions']++;
                }

                /*
                 * Date de démonstration déterministe :
                 * une date fixe calculée à partir de la ligne Excel.
                 * Elle ne représente PAS la date réelle d'embauche.
                 */
                $hireDate = Carbon::parse('2018-01-01')
                    ->addDays($index * 13)
                    ->startOfDay();

                // Vérifier si cette ligne a déjà été importée.
                $existing = Employee::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('company_id', $company->id)
                    ->where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->where('department_id', $department->id)
                    ->where('position_id', $position->id)
                    ->whereDate('hire_date', $hireDate->toDateString())
                    ->exists();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $matricule = $generator->next(
                    $department,
                    $hireDate,
                    $firstName,
                    $lastName
                );

                Employee::forceCreate([
                    'company_id' => $company->id,
                    'matricule' => $matricule,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'manager_id' => null,
                    'hire_date' => $hireDate->toDateString(),
                    'status' => 'active',

                    // Données fictives, uniquement pour les tests.
                    'children_count' => $index % 4,

                    // Informations personnelles non fournies par Excel.
                    'gender' => null,
                    'birth_date' => null,
                    'birth_place' => null,
                    'nationality' => null,
                    'marital_status' => null,
                    'spouse_works' => null,
                    'is_expatriate' => false,
                ]);

                $created['employees']++;
            }

            // Afficher le bilan avant de décider de conserver les écritures.
            $this->command?->newLine();
            $this->command?->info(
                $dryRun
                    ? 'SIMULATION — aucune modification ne sera conservée.'
                    : 'IMPORT IENG MALI'
            );

            $this->command?->table(
                ['Élément', 'Nombre'],
                [
                    ['Entreprise créée', $created['company'] ? 1 : 0],
                    ['Départements à créer', $created['departments']],
                    ['Postes à créer', $created['positions']],
                    ['Employés à créer', $created['employees']],
                    ['Employés déjà présents / ignorés', $skipped],
                    ['Lignes en erreur', count($errors)],
                ]
            );

            foreach ($errors as $error) {
                $this->command?->warn($error);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->command?->info(
                    'Simulation terminée : transaction annulée.'
                );
            } else {
                DB::commit();
                $this->command?->info(
                    'Import terminé : transaction validée.'
                );
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lire le CSV en acceptant la virgule ou le point-virgule.
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(
                'Impossible d’ouvrir le fichier CSV.'
            );
        }

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false) {
                return [];
            }

            $delimiter = substr_count($firstLine, ';')
                > substr_count($firstLine, ',')
                ? ';'
                : ',';

            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter);

            if (! is_array($headers)) {
                throw new RuntimeException(
                    'En-têtes CSV illisibles.'
                );
            }

            $headers = array_map(
                fn ($header) => mb_strtolower(
                    trim(
                        preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            (string) $header
                        )
                    )
                ),
                $headers
            );

            $aliases = [
                'first_name' => ['first name', 'first_name'],
                'last_name' => ['last name', 'last_name'],
                'position' => ['position'],
                'department' => ['department'],
            ];

            $columns = [];

            foreach ($aliases as $key => $accepted) {
                $column = null;

                foreach ($accepted as $alias) {
                    $found = array_search(
                        $alias,
                        $headers,
                        true
                    );

                    if ($found !== false) {
                        $column = $found;
                        break;
                    }
                }

                if ($column === null) {
                    throw new RuntimeException(
                        "Colonne obligatoire absente : {$key}"
                    );
                }

                $columns[$key] = $column;
            }

            $rows = [];

            while (($values = fgetcsv(
                $handle,
                0,
                $delimiter
            )) !== false) {
                if (
                    count($values) === 1 &&
                    trim((string) $values[0]) === ''
                ) {
                    continue;
                }

                $row = [];

                foreach ($columns as $key => $column) {
                    $row[$key] = trim(
                        (string) ($values[$column] ?? '')
                    );
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
