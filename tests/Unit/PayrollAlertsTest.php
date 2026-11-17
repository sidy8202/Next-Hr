<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollAlerts;
use PHPUnit\Framework\TestCase;

class PayrollAlertsTest extends TestCase
{
    private function keys(array $f): array
    {
        return array_column(PayrollAlerts::build($f), 'key');
    }

    public function test_nothing_to_report(): void
    {
        $this->assertSame([], PayrollAlerts::build(['period_status' => 'calculated', 'employees' => 12, 'employees_with_salary' => 12, 'legal_state' => 'validated']));
    }

    public function test_missing_salary_is_critical_and_singular_or_plural(): void
    {
        $a = PayrollAlerts::build(['employees' => 12, 'employees_with_salary' => 11]);

        $this->assertSame('missing_salary', $a[0]['key']);
        $this->assertSame('critical', $a[0]['severity']);
        $this->assertSame('1 employé sans salaire en vigueur', $a[0]['title']);
        $this->assertSame('employee_salaries', $a[0]['target']['resource']);

        $this->assertSame('3 employés sans salaire en vigueur', PayrollAlerts::build(['employees' => 12, 'employees_with_salary' => 9])[0]['title']);
    }

    /** Aucun employé : pas d'alerte de salaire manquant (l'assistant s'en charge). */
    public function test_no_employee_means_no_missing_salary_alert(): void
    {
        $this->assertSame([], $this->keys(['employees' => 0, 'employees_with_salary' => 0]));
    }

    public function test_pending_checks_only_while_calculated(): void
    {
        $this->assertSame(['pending_checks'], $this->keys(['period_status' => 'calculated', 'pending_checks' => 2]));
        $this->assertSame([], $this->keys(['period_status' => 'closed', 'pending_checks' => 2]));
        $this->assertSame([], $this->keys(['period_status' => 'draft', 'pending_checks' => 2]));
    }

    public function test_pending_variables_are_ignored_once_closed(): void
    {
        $this->assertSame(['pending_variables'], $this->keys(['period_status' => 'calculated', 'pending_variables' => 4]));
        $this->assertSame([], $this->keys(['period_status' => 'closed', 'pending_variables' => 4]));
    }

    public function test_skipped_employees_need_a_calculated_month(): void
    {
        $this->assertSame(['skipped'], $this->keys(['period_status' => 'closed', 'skipped' => 1]));
        $this->assertSame([], $this->keys(['period_status' => 'none', 'skipped' => 1]));
    }

    public function test_legal_rules_alerts_have_no_target(): void
    {
        $missing = PayrollAlerts::build(['legal_state' => 'missing']);
        $this->assertSame('legal_missing', $missing[0]['key']);
        $this->assertSame('critical', $missing[0]['severity']);
        $this->assertNull($missing[0]['target']);

        $partial = PayrollAlerts::build(['legal_state' => 'to_validate', 'legal_total' => 5, 'legal_validated' => 3]);
        $this->assertSame('Règles légales : 3 validées sur 5', $partial[0]['title']);
        $this->assertSame(2, $partial[0]['count']);
        $this->assertNull($partial[0]['target']);
    }

    public function test_loans_alert_mentions_the_month_installments(): void
    {
        $a = PayrollAlerts::build(['active_loans' => 2, 'installments_count' => 2, 'installments_amount' => 75000]);

        $this->assertSame('2 prêts ou avances en cours', $a[0]['title']);
        $this->assertSame('Échéances de ce mois : 75 000 FCFA (2 échéances).', $a[0]['detail']);

        $none = PayrollAlerts::build(['active_loans' => 1]);
        $this->assertSame('Aucune échéance à prélever ce mois.', $none[0]['detail']);
    }

    /** Critique d'abord, puis à surveiller, puis information ; ordre d'insertion à gravité égale. */
    public function test_sorted_by_severity_then_insertion_order(): void
    {
        $keys = $this->keys([
            'period_status' => 'calculated', 'employees' => 12, 'employees_with_salary' => 10, 'pending_checks' => 1, 'pending_variables' => 2,
            'skipped' => 1, 'legal_state' => 'to_validate', 'legal_total' => 4, 'legal_validated' => 1, 'off_grid' => 1, 'active_loans' => 1,
        ]);

        $this->assertSame(['missing_salary', 'pending_checks', 'skipped', 'pending_variables', 'legal_to_validate', 'off_grid', 'loans'], $keys);
    }

    public function test_alerts_never_carry_personal_data(): void
    {
        foreach (PayrollAlerts::build(['employees' => 5, 'employees_with_salary' => 1, 'period_status' => 'calculated', 'pending_checks' => 1, 'off_grid' => 1, 'active_loans' => 1]) as $a) {
            $this->assertSame(['key', 'severity', 'count', 'title', 'detail', 'target'], array_keys($a));
        }
    }
}
