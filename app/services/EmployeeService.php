<?php

namespace App\Services;

use App\Mail\AccountCreated;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Toute la logique de création / modification d'un employé (contrat, accès, pièces jointes).
 * Les composants Livewire VALIDENT ; ce service ENREGISTRE, dans une transaction : tout ou rien.
 */
class EmployeeService
{
    private const IDENTITY_FIELDS = [
        'first_name', 'last_name', 'gender', 'birth_date', 'birth_place', 'nationality', 'national_id',
        'marital_status', 'spouse_works', 'children_count', 'is_expatriate', 'work_permit_number',
        'work_permit_expires_at', 'phone', 'work_email', 'personal_email', 'address',
        'emergency_contact_name', 'emergency_contact_phone',
    ];

    public function __construct(private MatriculeGenerator $matricules)
    {
    }

    /** Ressource de droits associée à un type de document (confidentiel ou non). */
    public static function documentResource(string $type): string
    {
        $resource = config("documents.types.{$type}.sensitive") ? 'documents_sensitive' : 'documents';

        return array_key_exists($resource, config('permissions.resources')) ? $resource : 'documents';
    }

    /**
     * Crée un ou plusieurs employés (+ contrat, + accès, + pièces jointes) dans UNE transaction.
     * Chaque élément de $people porte SA date d'embauche, SON type de contrat, etc.
     *
     * @param  array<int, array>  $people
     * @return array{employees: array<int, Employee>, credentials: array<int, array>}
     */
    public function createMany(array $people): array
    {
        $result = ['employees' => [], 'credentials' => []];
        $stored = [];

        try {
            DB::transaction(function () use ($people, &$result, &$stored) {
                // Si la transaction est rejouée (interblocage), on repart de zéro.
                $this->deleteFiles($stored);
                $stored = [];
                $result = ['employees' => [], 'credentials' => []];

                foreach ($people as $person) {
                    $employee = $this->createOne($person);
                    $result['employees'][] = $employee;

                    $this->storeDocuments($employee, $person['documents'] ?? [], $stored);

                    if (! empty($person['create_account'])) {
                        $result['credentials'][] = $this->createAccount($employee, $person);
                    }
                }

                AuditLogger::log('created', 'employees', null, null, ['ids' => collect($result['employees'])->pluck('id')->all()]);
            }, 3);
        } catch (Throwable $e) {
            $this->deleteFiles($stored); // pas de fichiers orphelins si tout est annulé

            throw $e;
        }

        return $result;
    }

    public function update(Employee $employee, array $person): Employee
    {
        $stored = [];

        try {
            DB::transaction(function () use ($employee, $person, &$stored) {
                $payload = $this->identityAttributes($person) + [
                    'department_id' => (int) $person['department_id'],
                    'position_id' => (int) $person['position_id'],
                    'manager_id' => ! empty($person['manager_id']) ? (int) $person['manager_id'] : null,
                    'hire_date' => $person['hire_date'],
                ];

                $old = $employee->only(array_keys($payload));
                $employee->fill($payload);
                $dirty = $employee->getDirty();
                $employee->save();

                if ($dirty) {
                    AuditLogger::log('updated', 'employees', $employee->id, array_intersect_key($old, $dirty), $dirty);
                }

                $this->storeDocuments($employee, $person['documents'] ?? [], $stored);
            });
        } catch (Throwable $e) {
            $this->deleteFiles($stored);

            throw $e;
        }

        return $employee;
    }

    /**
     * Envoie les identifiants par email APRÈS l'enregistrement (jamais dans la transaction) :
     * un échec d'envoi n'annule donc jamais la création.
     *
     * @return array<string, string> matricule => sent | failed | none
     */
    public function sendCredentials(array $credentials): array
    {
        $status = [];
        $company = auth()->user()->company?->name ?? config('app.name');

        foreach ($credentials as $c) {
            $to = array_values(array_unique(array_filter($c['send_to'] ?? [])));

            if (! $to) {
                $status[$c['matricule']] = 'none';

                continue;
            }

            try {
                Mail::to($to)->send(new AccountCreated($c['name'], $company, $c['login_email'], $c['password'], route('login')));
                $status[$c['matricule']] = 'sent';
            } catch (Throwable $e) {
                report($e);
                $status[$c['matricule']] = 'failed';
            }
        }

        return $status;
    }

    // ---------- Internes ----------

    private function createOne(array $p): Employee
    {
        $department = Department::findOrFail($p['department_id']); // filtré par entreprise
        $hire = Carbon::parse($p['hire_date']);
        $type = $p['contract_type'];

        $employee = Employee::create($this->identityAttributes($p) + [
            'matricule' => $this->matricules->next($department, $hire, $p['first_name'], $p['last_name'], $type),
            'department_id' => $department->id,
            'position_id' => (int) $p['position_id'],
            'manager_id' => $department->head_id,
            'hire_date' => $hire->toDateString(),
            'status' => 'active',
        ]);

        Contract::create([
            'employee_id' => $employee->id,
            'type' => $type,
            'start_date' => $hire->toDateString(),
            'end_date' => $type === 'CDI' ? null : $hire->copy()->addMonths((int) ($p['duration_months'] ?? 0))->subDay()->toDateString(),
            'base_salary' => (int) $p['base_salary'],
            'currency' => auth()->user()->company?->currency ?? 'XOF',
            'renewal_number' => 0,
            'status' => 'active',
        ]);

        return $employee;
    }

    private function createAccount(Employee $employee, array $p): array
    {
        $plain = Str::password(12, symbols: false);
        $login = Str::lower(trim($p['login_email']));

        User::forceCreate([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'name' => $employee->full_name,
            'email' => $login,
            'password' => $plain, // haché par le cast « hashed » du modèle
            'is_active' => true,
            'must_change_password' => true,
        ]);

        return [
            'matricule' => $employee->matricule,
            'name' => $employee->full_name,
            'login_email' => $login,
            'password' => $plain,
            'send_to' => array_values(array_filter($p['send_to'] ?? [])),
        ];
    }

    private function identityAttributes(array $p): array
    {
        $none = fn ($v) => ($v === '' || $v === null) ? null : $v;
        $out = [];

        foreach (self::IDENTITY_FIELDS as $field) {
            $out[$field] = $none($p[$field] ?? null);
        }

        $out['first_name'] = trim((string) ($out['first_name'] ?? ''));
        $out['last_name'] = trim((string) ($out['last_name'] ?? ''));
        $out['is_expatriate'] = (bool) ($p['is_expatriate'] ?? false);

        // Cast propre quelle que soit la provenance (input number, boutons +/- ou tableau array)
        $rawChildren = $p['children_count'] ?? $out['children_count'] ?? 0;
        $out['children_count'] = max(0, (int) $rawChildren);

        // Gestion de la situation du conjoint
        if (array_key_exists('spouse_works', $p) && $p['spouse_works'] !== null) {
            $out['spouse_works'] = (bool) $p['spouse_works'];
        } else {
            $out['spouse_works'] = (($out['marital_status'] ?? '') === 'married' && isset($out['spouse_works']))
                ? (bool) $out['spouse_works']
                : null;
        }

        return $out;
    }

    /** Range les fichiers dans le dossier PRIVÉ (storage/app/private) et crée les lignes « documents ». */
    private function storeDocuments(Employee $employee, array $documents, array &$stored): void
    {
        foreach ($documents as $doc) {
            if (empty($doc['file'])) {
                continue;
            }

            $file = $doc['file'];
            $path = $file->storeAs(
                "companies/{$employee->company_id}/employees/{$employee->id}",
                Str::uuid().'.'.$file->extension(),
                'local'
            );
            $stored[] = $path;

            Document::create([
                'employee_id' => $employee->id,
                'type' => $doc['type'],
                'title' => trim((string) ($doc['title'] ?? '')) ?: (config("documents.types.{$doc['type']}.label") ?? $doc['type']),
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function deleteFiles(array $paths): void
    {
        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }
}
