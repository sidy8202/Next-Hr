<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeePayProfile;
use App\Models\EmployeeSalary;
use App\Models\PayItem;
use App\Models\PayProfile;
use App\Models\PayrollPeriod;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\PayrollSetupState;
use App\Models\PayslipTemplate;
use App\Models\SalaryGrid;

/**
 * Collecte, en base, les « faits » dont SetupProgress a besoin. Lecture seule : ne crée aucune ligne.
 * Les employés sont comptés comme dans le reste de la paie (tous les employés de l'entreprise, comme PayrollRunner).
 */
class SetupFacts
{
    /** @return array<string, mixed> */
    public static function facts(int $companyId): array
    {
        $today = now()->toDateString();
        $state = PayrollSetupState::peek($companyId);
        $confirmed = $state ? $state->confirmedKeys() : [];

        return [
            'settings_confirmed' => in_array('settings', $confirmed, true),
            'variable_settings_confirmed' => in_array('variable_settings', $confirmed, true),
            'grids' => SalaryGrid::where('company_id', $companyId)->where('is_active', true)->count(),
            'system_items' => PayItem::where('company_id', $companyId)->where('is_system', true)->count(),
            'profiles' => PayProfile::where('company_id', $companyId)->where('is_active', true)->count(),
            'employees' => Employee::where('company_id', $companyId)->count(),
            'employees_with_salary' => EmployeeSalary::where('company_id', $companyId)->inForceOn($today)->distinct()->count('employee_id'),
            'employees_with_profile' => EmployeePayProfile::where('company_id', $companyId)->inForceOn($today)->distinct()->count('employee_id'),
            'default_templates' => PayslipTemplate::where('company_id', $companyId)->where('is_default', true)->count(),
            'simulation_done' => (bool) ($state && $state->simulation_done_at),
            'periods_calculated' => PayrollPeriod::where('company_id', $companyId)->whereIn('status', ['calculated', 'closed'])->count(),
            'skipped' => $state ? $state->skippedKeys() : [],
        ];
    }

    /**
     * Règles légales de la plateforme pour le pays de l'entreprise.
     *
     * @return array{state: string, total: int, validated: int, last_validated_at: ?string}
     */
    public static function legal(int $companyId): array
    {
        $country = (string) (PayrollSetting::where('company_id', $companyId)->value('country') ?: 'ML');
        $legal = SetupProgress::legalState(PayrollRule::activeOn(now()->toDateString(), $country));

        $last = PayrollRule::where('country', $country)->where('status', 'validated')->max('validated_at');

        return $legal + ['last_validated_at' => $last ? (string) $last : null];
    }
}
