<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalary;

/**
 * Net connu de chaque employé : la référence que la paie vérifie.
 * Règle : un net déjà renseigné ne se remplace jamais ici. Un nouveau net passe par une nouvelle ligne de salaire datée.
 */
final class PayrollNets
{
    /** Net du contrat calculé par le moteur : mois complet, sans variables du mois. @param array{base: int, supplement: int, mode: string, target_net: ?int}|null $salary */
    public static function calculatedContractNet(int $employeeId, string $period, ?array $salary = null): int
    {
        $built = PayrollInputBuilder::build($employeeId, $period, ['without_variables' => true, 'full_month' => true] + ($salary ? ['salary' => $salary] : []));

        return PayrollCalculator::compute($built['input'])['totals']['net_contract'];
    }

    /** Sursalaire que le moteur retient pour ce salaire (net garanti : recalculé ; brut fixé : celui enregistré). Mois complet, sans variables. */
    public static function calculatedSupplement(int $employeeId, string $period): int
    {
        $built = PayrollInputBuilder::build($employeeId, $period, ['without_variables' => true, 'full_month' => true]);

        return PayrollCalculator::compute($built['input'])['supplement']['used'];
    }

    /**
     * Renseigne le net connu à partir d'une liste « matricule => net » : uniquement là où il est vide.
     *
     * @param  array<string, int>  $pairs
     * @return array<int, array{matricule: string, status: string, detail: string}> status : set | same | different | not_found | no_salary
     */
    public static function applyKnownNets(array $pairs): array
    {
        $report = [];
        $today = now()->toDateString();

        foreach ($pairs as $matricule => $net) {
            $matricule = (string) $matricule;
            $employee = Employee::where('matricule', $matricule)->first();

            if (! $employee) {
                $report[] = ['matricule' => $matricule, 'status' => 'not_found', 'detail' => 'Aucun employé avec ce matricule.'];

                continue;
            }

            $salary = EmployeeSalary::where('employee_id', $employee->id)->inForceOn($today)->orderByDesc('effective_from')->first();

            if (! $salary) {
                $report[] = ['matricule' => $matricule, 'status' => 'no_salary', 'detail' => 'Aucun salaire en vigueur : saisissez-le d\'abord.'];

                continue;
            }

            if ($salary->target_net === null) {
                $salary->update(['target_net' => $net]);
                $report[] = ['matricule' => $matricule, 'status' => 'set', 'detail' => 'Net connu enregistré : ' . number_format($net, 0, ',', ' ') . '.'];
            } elseif ((int) $salary->target_net === $net) {
                $report[] = ['matricule' => $matricule, 'status' => 'same', 'detail' => 'Déjà enregistré à ce montant.'];
            } else {
                $report[] = ['matricule' => $matricule, 'status' => 'different', 'detail' => 'Un net différent est déjà enregistré (' . number_format((int) $salary->target_net, 0, ',', ' ') . ') : pour le changer, créez une nouvelle ligne de salaire datée.'];
            }
        }

        return $report;
    }

    /**
     * Complète les nets connus manquants (salaires en « brut fixé ») avec le net calculé par le moteur.
     * À utiliser au démarrage : le net enregistré est alors un point de départ, à comparer ensuite à vos bulletins existants.
     *
     * @return array{filled: int, failed: int}
     */
    public static function fillMissingFromCalculation(string $period): array
    {
        $filled = 0;
        $failed = 0;

        foreach (EmployeeSalary::inForceOn(now()->toDateString())->whereNull('target_net')->where('mode', 'gross')->get() as $salary) {
            try {
                $net = self::calculatedContractNet((int) $salary->employee_id, $period);
                $salary->update(['target_net' => $net]);
                $filled++;
            } catch (\RuntimeException) {
                $failed++;
            }
        }

        return ['filled' => $filled, 'failed' => $failed];
    }
}
