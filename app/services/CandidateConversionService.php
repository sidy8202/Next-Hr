<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transforme un candidat « Embauché » en employé en réutilisant EmployeeService::createMany()
 * (matricule, contrat, compte d'accès : exactement la même logique que la création d'employé).
 */
class CandidateConversionService
{
    public function __construct(private EmployeeService $employees)
    {
    }

    /** @return array{employee: \App\Models\Employee, credentials: array<int, array>} */
    public function convert(Application $application, array $person): array
    {
        return DB::transaction(function () use ($application, $person) {
            $app = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $candidate = Candidate::whereKey($app->candidate_id)->lockForUpdate()->firstOrFail();

            if ($app->stage !== 'hired') {
                throw ValidationException::withMessages([
                    'convert' => 'Seule une candidature au stade « Embauché » peut devenir un employé.',
                ]);
            }
            if ($candidate->employee_id) {
                throw ValidationException::withMessages(['convert' => 'Ce candidat a déjà une fiche employé.']);
            }

            $result = $this->employees->createMany([$person]);
            $employee = $result['employees'][0];

            Candidate::whereKey($candidate->id)->update(['employee_id' => $employee->id]);

            AuditLogger::log('converted_to_employee', 'candidates', $candidate->id, null, ['employee_id' => $employee->id]);

            return ['employee' => $employee, 'credentials' => $result['credentials']];
        });
    }
}
