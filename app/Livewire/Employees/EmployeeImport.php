<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\AuthorizesResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SalaryStep;
use App\Services\EmployeeService;
use App\Services\PlanGate;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Importer des employés')]
class EmployeeImport extends Component
{
    use AuthorizesResource, WithFileUploads;

    public $file = null;

    public array $headers = [];

    /** @var array<string, int|string|null> */
    public array $mapping = [];

    public string $stage = 'upload';

    public array $previewRows = [];

    public array $rowErrors = [];

    public array $messages = [];

    public int $validCount = 0;

    public int $createdCount = 0;

    private const FIELDS = [
        'matricule' => ['label' => 'Code / matricule', 'required' => false, 'aliases' => ['matricule', 'matricule employe', 'code employe', 'employee id', 'employee code', 'staff id']],
        'first_name' => ['label' => 'Prénom', 'required' => true, 'aliases' => ['prenom', 'first name', 'firstname', 'given name', 'forename']],
        'last_name' => ['label' => 'Nom', 'required' => true, 'aliases' => ['nom', 'nom de famille', 'last name', 'lastname', 'surname', 'family name']],
        'gender' => ['label' => 'Sexe', 'required' => false, 'aliases' => ['sexe', 'genre', 'gender', 'sex']],
        'birth_date' => ['label' => 'Date de naissance', 'required' => false, 'aliases' => ['date de naissance', 'naissance', 'birth date', 'date of birth', 'dob']],
        'nationality' => ['label' => 'Nationalité', 'required' => false, 'aliases' => ['nationalite', 'nationality', 'citizenship']],
        'national_id' => ['label' => 'Pièce d’identité', 'required' => false, 'aliases' => ['numero piece', 'numero identite', 'cni', 'national id', 'identity number', 'id number']],
        'marital_status' => ['label' => 'Situation matrimoniale', 'required' => false, 'aliases' => ['situation matrimoniale', 'situation familiale', 'marital status', 'civil status']],
        'spouse_works' => ['label' => 'Le conjoint travaille', 'required' => false, 'aliases' => ['conjoint travaille', 'le conjoint travaille', 'spouse works', 'spouse employed']],
        'children_count' => ['label' => 'Nombre d’enfants', 'required' => false, 'aliases' => ['nombre enfants', 'nombre d enfants', 'enfants', 'children', 'children count', 'number of children']],
        'is_expatriate' => ['label' => 'Expatrié', 'required' => false, 'aliases' => ['expatrie', 'expatriate', 'is expatriate']],
        'work_permit_number' => ['label' => 'N° permis de travail', 'required' => false, 'aliases' => ['numero permis travail', 'permis travail', 'work permit number']],
        'work_permit_expires_at' => ['label' => 'Expiration du permis', 'required' => false, 'aliases' => ['expiration permis', 'work permit expiry', 'work permit expires at']],
        'phone' => ['label' => 'Téléphone', 'required' => false, 'aliases' => ['telephone', 'tel', 'phone', 'mobile', 'phone number']],
        'work_email' => ['label' => 'E-mail professionnel', 'required' => false, 'aliases' => ['email professionnel', 'e mail professionnel', 'email pro', 'work email', 'business email']],
        'personal_email' => ['label' => 'E-mail personnel', 'required' => false, 'aliases' => ['email personnel', 'personal email', 'email prive']],
        'address' => ['label' => 'Adresse', 'required' => false, 'aliases' => ['adresse', 'address', 'domicile']],
        'emergency_contact_name' => ['label' => 'Contact d’urgence', 'required' => false, 'aliases' => ['contact urgence', 'emergency contact', 'emergency contact name']],
        'emergency_contact_phone' => ['label' => 'Téléphone du contact d’urgence', 'required' => false, 'aliases' => ['telephone urgence', 'emergency phone', 'emergency contact phone']],
        'department' => ['label' => 'Département (code ou nom)', 'required' => true, 'aliases' => ['departement', 'code departement', 'department', 'department code', 'service', 'division']],
        'position' => ['label' => 'Poste', 'required' => true, 'aliases' => ['poste', 'fonction', 'intitule poste', 'position', 'job title', 'title']],
        'status' => ['label' => 'Statut', 'required' => false, 'aliases' => ['statut', 'status', 'employee status', 'employment status']],
        'hire_date' => ['label' => 'Date d’embauche', 'required' => true, 'aliases' => ['date embauche', 'date d embauche', 'date entree', 'hire date', 'start date', 'joining date']],
        'contract_type' => ['label' => 'Type de contrat', 'required' => true, 'aliases' => ['type contrat', 'type de contrat', 'contrat', 'contract type', 'contract']],
        'duration_months' => ['label' => 'Durée du contrat (mois)', 'required' => false, 'aliases' => ['duree contrat', 'duree du contrat mois', 'duree mois', 'duration months', 'contract duration']],
        'base_salary' => ['label' => 'Salaire brut de base', 'required' => true, 'aliases' => ['salaire de base', 'salaire brut', 'salaire brut de base', 'base salary', 'gross salary', 'salary']],
    ];

    public function mount(): void
    {
        $this->authorizeAction('create', 'employees');
    }

    public function updatedFile(): void
    {
        $this->authorizeAction('create', 'employees');
        $this->resetValidation();
        $this->messages = [];
        $this->rowErrors = [];
        $this->previewRows = [];
        $this->validCount = 0;
        $this->stage = 'upload';

        $validated = $this->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:csv,txt'],
        ]);

        try {
            $table = $this->readCsv($validated['file']->getRealPath());
            $this->headers = $table['headers'];
            $this->mapping = $this->suggestMapping($this->headers);
            $this->stage = 'mapping';
        } catch (\RuntimeException $e) {
            $this->addError('file', $e->getMessage());
        }
    }

    public function preview(): void
    {
        $this->authorizeAction('create', 'employees');
        $this->resetValidation();
        $this->messages = [];

        if (! $this->file) {
            $this->addError('file', 'Choisissez un fichier CSV avant de continuer.');

            return;
        }

        try {
            $validated = $this->validate(['file' => ['required', 'file', 'max:10240', 'extensions:csv,txt']]);
            $table = $this->readCsv($validated['file']->getRealPath());
            $this->headers = $table['headers'];
            [$people, $errors, $rows] = $this->validateRows($table['rows']);
            $this->validCount = count($people);
            $this->rowErrors = $errors;
            $this->previewRows = $rows;
            $this->stage = 'preview';

            if (! $table['rows']) {
                $this->messages[] = 'Le fichier ne contient aucune ligne à importer.';
            } elseif (! $errors) {
                $this->messages[] = "{$this->validCount} ligne(s) prête(s) à être importée(s).";
            }
        } catch (\RuntimeException $e) {
            $this->addError('file', $e->getMessage());
        }
    }

    public function import(EmployeeService $service)
    {
        $this->authorizeAction('create', 'employees');

        if (! $this->file) {
            $this->addError('file', 'Le fichier temporaire a expiré. Veuillez le sélectionner à nouveau.');

            return;
        }

        try {
            $validated = $this->validate(['file' => ['required', 'file', 'max:10240', 'extensions:csv,txt']]);
            $table = $this->readCsv($validated['file']->getRealPath());
            [$people, $errors] = $this->validateRows($table['rows']);
        } catch (\RuntimeException $e) {
            $this->addError('file', $e->getMessage());

            return;
        }

        $this->rowErrors = $errors;
        $this->validCount = count($people);

        if ($errors || ! $people) {
            $this->stage = 'preview';
            $this->addError('import', $errors ? 'Corrigez toutes les lignes signalées avant l’import.' : 'Aucune ligne valide à importer.');

            return;
        }

        $companyId = (int) auth()->user()->company_id;

        if (! PlanGate::canAddEmployees($companyId, count($people))) {
            $this->addError('import', 'Le nombre d’employés dépasse le plafond de votre formule. Réduisez le lot ou changez de formule.');

            return;
        }

        try {
            $result = $service->createMany($people);
            $this->createdCount = count($result['employees']);
            $this->stage = 'done';
            $this->file = null;
            $this->messages = ["{$this->createdCount} employé(s) importé(s)."];
        } catch (UniqueConstraintViolationException $e) {
            report($e);
            $this->addError('import', 'Import annulé : un code employé existe déjà. Vérifiez les matricules du fichier.');
        } catch (Throwable $e) {
            report($e);
            $this->addError('import', 'Import annulé : aucune ligne n’a été enregistrée. Vérifiez les données puis réessayez.');
        }
    }

    public function downloadTemplate()
    {
        $this->authorizeAction('create', 'employees');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($field) => self::FIELDS[$field]['label'], array_keys(self::FIELDS)), ';');
            fclose($out);
        }, 'modele-import-employes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.employees.employee-import', ['fields' => self::FIELDS]);
    }

    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>} */
    private function readCsv(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new \RuntimeException('Le fichier CSV est vide ou illisible.');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = ',';
        $bestCount = 1;

        foreach ([";", ',', "\t", '|'] as $candidate) {
            $count = count(str_getcsv($firstLine, $candidate, '"', ''));

            if ($count > $bestCount) {
                $bestCount = $count;
                $delimiter = $candidate;
            }
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        $rawHeaders = fgetcsv($stream, 0, $delimiter, '"', '');

        if (! is_array($rawHeaders) || count($rawHeaders) < 2) {
            fclose($stream);
            throw new \RuntimeException('Impossible de détecter les colonnes. Vérifiez le séparateur du fichier CSV.');
        }

        $headers = array_map(fn ($header, $i) => trim((string) $header) ?: 'Colonne '.($i + 1), $rawHeaders, array_keys($rawHeaders));
        $rows = [];

        while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            if (count($row) === 1 && trim((string) ($row[0] ?? '')) === '') {
                continue;
            }

            if (count($rows) >= 1000) {
                fclose($stream);
                throw new \RuntimeException('Le fichier dépasse la limite de 1 000 lignes. Importez-le en plusieurs lots.');
            }

            $rows[] = array_pad(array_slice($row, 0, count($headers)), count($headers), '');
        }

        fclose($stream);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function suggestMapping(array $headers): array
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalize($header), $headers);
        $mapping = [];

        foreach (self::FIELDS as $field => $meta) {
            $aliases = array_map(fn ($alias) => $this->normalize($alias), $meta['aliases']);
            $index = array_search(true, array_map(fn ($header) => in_array($header, $aliases, true), $normalizedHeaders), true);
            $mapping[$field] = $index === false ? '' : $index;
        }

        return $mapping;
    }

    /** @return array{0: array<int, array>, 1: array<int, array<int, string>>, 2: array<int, array>} */
    private function validateRows(array $rows): array
    {
        $companyId = (int) auth()->user()->company_id;
        $people = [];
        $errors = [];
        $preview = [];
        $seenCodes = [];
        $seenIdentity = [];
        $required = collect(self::FIELDS)->filter(fn ($meta) => $meta['required'])->keys()->all();
        $missingMappings = array_values(array_filter($required, fn ($field) => ! isset($this->mapping[$field]) || $this->mapping[$field] === ''));

        if ($missingMappings) {
            $this->addError('mapping', 'Associez une colonne à chaque champ obligatoire : '.implode(', ', array_map(fn ($field) => self::FIELDS[$field]['label'], $missingMappings)).'.');

            return [[], [], []];
        }

        $requiredIndexes = array_map(fn ($field) => (string) $this->mapping[$field], $required);
        if (count(array_unique($requiredIndexes)) !== count($requiredIndexes)) {
            $this->addError('mapping', 'Chaque champ obligatoire doit être associé à une colonne différente.');

            return [[], [], []];
        }

        foreach ($rows as $offset => $row) {
            $line = $offset + 2;
            $values = [];

            foreach (self::FIELDS as $field => $meta) {
                $index = $this->mapping[$field] ?? '';
                $values[$field] = $index === '' ? '' : trim((string) ($row[(int) $index] ?? ''));
            }

            if (! array_filter($values, fn ($value) => $value !== '')) {
                continue;
            }

            $rowErrors = [];
            $firstName = trim($values['first_name']);
            $lastName = trim($values['last_name']);
            $hireDate = $this->dateValue($values['hire_date']);
            $birthDate = $this->dateValue($values['birth_date']);
            $permitDate = $this->dateValue($values['work_permit_expires_at']);
            $contractType = $this->contractType($values['contract_type']);
            $salary = $this->amount($values['base_salary']);
            $department = $this->findDepartment($companyId, $values['department']);
            $position = $department ? $this->findPosition($companyId, $department->id, $values['position']) : null;
            $status = $this->employeeStatus($values['status']);
            $salaryStep = $salary !== null && $position?->salary_grade_id
                ? SalaryStep::whereHas('grade.grid')->where('salary_grade_id', $position->salary_grade_id)->where('amount', $salary)->first()
                : null;

            if ($firstName === '' || mb_strlen($firstName) > 100) $rowErrors[] = 'Prénom absent ou trop long.';
            if ($lastName === '' || mb_strlen($lastName) > 100) $rowErrors[] = 'Nom absent ou trop long.';
            if (! $hireDate) $rowErrors[] = 'Date d’embauche invalide (formats acceptés : AAAA-MM-JJ ou JJ/MM/AAAA).';
            if (! $department) $rowErrors[] = 'Département introuvable : « '.$values['department'].' ».';
            if ($department && ! $position) $rowErrors[] = 'Poste introuvable dans ce département : « '.$values['position'].' ».';
            if ($values['status'] !== '' && $status === null) $rowErrors[] = 'Statut non reconnu. Valeurs acceptées : actif ou suspendu. Les sorties se traitent dans le parcours de sortie.';
            if (! $contractType) $rowErrors[] = 'Type de contrat non reconnu (CDI, CDD ou STAGE).';
            if ($contractType && $contractType !== 'CDI' && (! ctype_digit($values['duration_months']) || (int) $values['duration_months'] < 1 || (int) $values['duration_months'] > 60)) $rowErrors[] = 'Durée obligatoire pour un CDD ou un stage (1 à 60 mois).';
            if ($salary === null || $salary < 0) $rowErrors[] = 'Salaire de base invalide.';
            if ($position?->salary_grade_id && $salary > 0 && ! $salaryStep && ! auth()->user()->canDo('update', 'employee_salaries')) $rowErrors[] = 'Le salaire ne correspond à aucun échelon de la grille du poste. Un droit de dérogation salariale est nécessaire.';
            if ($values['birth_date'] !== '' && ! $birthDate) $rowErrors[] = 'Date de naissance invalide.';
            if ($birthDate && $birthDate >= now()->toDateString()) $rowErrors[] = 'La date de naissance doit être dans le passé.';
            if ($values['work_permit_expires_at'] !== '' && ! $permitDate) $rowErrors[] = 'Date d’expiration du permis invalide.';

            $gender = $this->gender($values['gender']);
            if ($values['gender'] !== '' && $gender === null) $rowErrors[] = 'Sexe non reconnu (M/F).';
            $maritalStatus = $this->maritalStatus($values['marital_status']);
            if ($values['marital_status'] !== '' && $maritalStatus === null) $rowErrors[] = 'Situation matrimoniale non reconnue.';
            if ($values['spouse_works'] !== '' && $this->booleanValue($values['spouse_works']) === null) $rowErrors[] = 'Valeur du champ « conjoint travaille » non reconnue (oui/non).';
            if ($values['is_expatriate'] !== '' && $this->booleanValue($values['is_expatriate']) === null) $rowErrors[] = 'Valeur du champ « expatrié » non reconnue (oui/non).';
            $children = $values['children_count'] === '' ? 0 : filter_var($values['children_count'], FILTER_VALIDATE_INT);
            if ($children === false || $children < 0 || $children > 30) $rowErrors[] = 'Nombre d’enfants invalide (0 à 30).';

            foreach (['work_email', 'personal_email'] as $emailField) {
                if ($values[$emailField] !== '' && ! filter_var($values[$emailField], FILTER_VALIDATE_EMAIL)) $rowErrors[] = self::FIELDS[$emailField]['label'].' invalide.';
            }

            $code = trim($values['matricule']);
            if (mb_strlen($code) > 50) $rowErrors[] = 'Le matricule dépasse 50 caractères.';
            if ($code !== '') {
                $codeKey = Str::lower($code);
                if (isset($seenCodes[$codeKey])) $rowErrors[] = 'Matricule déjà présent dans le fichier (ligne '.$seenCodes[$codeKey].').';
                elseif (Employee::withTrashed()->where('company_id', $companyId)->whereRaw('LOWER(matricule) = ?', [$codeKey])->exists()) $rowErrors[] = 'Matricule déjà utilisé dans cette entreprise.';
                $seenCodes[$codeKey] = $line;
            }

            if ($birthDate && $firstName !== '' && $lastName !== '') {
                $identityKey = $this->normalize($firstName.'|'.$lastName.'|'.$birthDate);
                if (isset($seenIdentity[$identityKey])) $rowErrors[] = 'Même nom et même date de naissance que la ligne '.$seenIdentity[$identityKey].'.';
                elseif (Employee::withTrashed()->where('company_id', $companyId)->whereRaw('LOWER(first_name) = ?', [Str::lower($firstName)])->whereRaw('LOWER(last_name) = ?', [Str::lower($lastName)])->whereDate('birth_date', $birthDate)->exists()) $rowErrors[] = 'Un employé avec ce nom et cette date de naissance existe déjà dans l’entreprise.';
                $seenIdentity[$identityKey] = $line;
            }

            $person = [
                'matricule' => $code ?: null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'nationality' => $values['nationality'] ?: null,
                'national_id' => $values['national_id'] ?: null,
                'marital_status' => $maritalStatus,
                'spouse_works' => $this->booleanValue($values['spouse_works']),
                'children_count' => $children === false ? 0 : (int) $children,
                'is_expatriate' => $this->booleanValue($values['is_expatriate']) ?? false,
                'work_permit_number' => $values['work_permit_number'] ?: null,
                'work_permit_expires_at' => $permitDate,
                'phone' => $values['phone'] ?: null,
                'work_email' => $values['work_email'] ?: null,
                'personal_email' => $values['personal_email'] ?: null,
                'address' => $values['address'] ?: null,
                'emergency_contact_name' => $values['emergency_contact_name'] ?: null,
                'emergency_contact_phone' => $values['emergency_contact_phone'] ?: null,
                'department_id' => $department?->id,
                'position_id' => $position?->id,
                'status' => $status ?? 'active',
                'hire_date' => $hireDate,
                'contract_type' => $contractType,
                'duration_months' => $contractType === 'CDI' ? null : (int) $values['duration_months'],
                'base_salary' => $salary,
                'salary' => [
                    'create' => $salary > 0,
                    'base' => $salary ?? 0,
                    'grade_id' => $salaryStep?->salary_grade_id,
                    'step_id' => $salaryStep?->id,
                    'mode' => 'gross',
                    'target_net' => null,
                    'override' => (bool) ($position?->salary_grade_id && $salary > 0 && ! $salaryStep),
                ],
                'leave_days_per_month' => null,
                'create_account' => false,
            ];

            if ($rowErrors) $errors[$line] = $rowErrors;
            else $people[] = $person;

            if (count($preview) < 8) {
                $preview[] = ['line' => $line, 'name' => trim($firstName.' '.$lastName), 'department' => $department?->name ?? $values['department'], 'position' => $position?->title ?? $values['position'], 'status' => $rowErrors ? 'error' : 'ok'];
            }
        }

        return [$people, $errors, $preview];
    }

    private function findDepartment(int $companyId, string $value): ?Department
    {
        if ($value === '') return null;

        $needle = $this->normalize($value);
        $matches = Department::where('company_id', $companyId)->get()->filter(fn ($department) =>
            $this->normalize((string) $department->code) === $needle || $this->normalize((string) $department->name) === $needle
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function findPosition(int $companyId, int $departmentId, string $value): ?Position
    {
        if ($value === '') return null;

        $needle = $this->normalize($value);
        $matches = Position::where('company_id', $companyId)->where('department_id', $departmentId)->get()->filter(fn ($position) => $this->normalize((string) $position->title) === $needle);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function dateValue(string $value): ?string
    {
        if ($value === '') return null;

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            $issues = \DateTime::getLastErrors();
            if ($date && ($issues === false || ($issues['warning_count'] === 0 && $issues['error_count'] === 0)) && $date->format($format) === $value) return $date->format('Y-m-d');
        }

        return null;
    }

    private function amount(string $value): ?int
    {
        if ($value === '') return null;

        $number = preg_replace('/[^0-9,.\-]/u', '', str_replace([' ', "\u{00A0}", "\u{202F}"], '', $value));
        if (preg_match('/[,.](\d{1,2})$/', $number, $decimal) && ! in_array($decimal[1], ['0', '00'], true)) return null;
        $number = preg_replace('/[,.]0{1,2}$/', '', $number) ?? $number;
        $digits = preg_replace('/[^0-9-]/u', '', $number);
        return $digits !== '' && preg_match('/^-?\d+$/', $digits) ? (int) $digits : null;
    }

    private function gender(string $value): ?string
    {
        return match ($this->normalize($value)) {
            'm', 'masculin', 'male', 'homme' => 'M',
            'f', 'feminin', 'female', 'femme' => 'F',
            '' => null,
            default => null,
        };
    }

    private function maritalStatus(string $value): ?string
    {
        return match ($this->normalize($value)) {
            'single', 'celibataire', 'celibataire sans enfant' => 'single',
            'married', 'marie', 'mariee' => 'married',
            'divorced', 'divorce', 'divorcee' => 'divorced',
            'widowed', 'veuf', 'veuve' => 'widowed',
            '' => null,
            default => null,
        };
    }

    private function contractType(string $value): ?string
    {
        return match ($this->normalize($value)) {
            'cdi', 'permanent', 'indefinite' => 'CDI',
            'cdd', 'fixed term', 'fixed-term', 'temporary' => 'CDD',
            'stage', 'stagiaire', 'internship', 'intern' => 'STAGE',
            default => null,
        };
    }

    private function employeeStatus(string $value): ?string
    {
        return match ($this->normalize($value)) {
            'active', 'actif', 'activee', 'en poste' => 'active',
            'suspended', 'suspendu', 'suspendue', 'inactive', 'inactif', 'inactivee' => 'suspended',
            '' => null,
            default => null,
        };
    }

    private function booleanValue(string $value): ?bool
    {
        return match ($this->normalize($value)) {
            '1', 'oui', 'yes', 'true', 'vrai', 'x' => true,
            '0', 'non', 'no', 'false', 'faux' => false,
            '' => null,
            default => null,
        };
    }

    private function normalize(string $value): string
    {
        $ascii = Str::ascii(Str::lower(trim($value)));

        return trim(preg_replace('/[^a-z0-9]+/i', ' ', $ascii) ?? '');
    }
}
