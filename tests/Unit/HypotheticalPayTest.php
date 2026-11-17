<?php

namespace Tests\Unit;

use App\Services\Payroll\HypotheticalPay;
use PHPUnit\Framework\TestCase;

class HypotheticalPayTest extends TestCase
{
    public function test_period_is_the_later_of_hire_month_and_current_month(): void
    {
        $this->assertSame('2026-12', HypotheticalPay::period('2026-12-10', '2026-11-15'));
        $this->assertSame('2026-11', HypotheticalPay::period('2020-01-05', '2026-11-15'));
        $this->assertSame('2026-11', HypotheticalPay::period(null, '2026-11-15'));
        $this->assertSame('2026-11', HypotheticalPay::period('abc', '2026-11-15'));
    }

    /** Le mois demandé par le simulateur prime ; un mois invalide retombe sur la règle de la date d'embauche. */
    public function test_requested_period_wins_when_valid(): void
    {
        $this->assertSame('2026-08', HypotheticalPay::resolvePeriod(['period' => '2026-08', 'hire_date' => '2026-12-10'], '2026-11-15'));
        $this->assertSame('2026-12', HypotheticalPay::resolvePeriod(['period' => '2026-13', 'hire_date' => '2026-12-10'], '2026-11-15'));
        $this->assertSame('2026-12', HypotheticalPay::resolvePeriod(['period' => 'abc', 'hire_date' => '2026-12-10'], '2026-11-15'));
        $this->assertSame('2026-11', HypotheticalPay::resolvePeriod([], '2026-11-15'));
    }

    public function test_seniority_counts_complete_years_until_month_end(): void
    {
        $this->assertSame(6, HypotheticalPay::seniorityYears('2020-03-01', '2026-11-30'));
        $this->assertSame(5, HypotheticalPay::seniorityYears('2020-12-01', '2026-11-30'));
        $this->assertSame(0, HypotheticalPay::seniorityYears('2027-01-01', '2026-11-30'));
        $this->assertSame(0, HypotheticalPay::seniorityYears(null, '2026-11-30'));
        $this->assertSame(0, HypotheticalPay::seniorityYears('', '2026-11-30'));
        $this->assertSame(0, HypotheticalPay::seniorityYears('pas une date', '2026-11-30'));
    }

    /** L'entrée respecte le contrat documenté par PayrollCalculator : mois complet, aucune variable, sursalaire nul. */
    public function test_assemble_builds_a_full_month_input_without_variables(): void
    {
        $in = HypotheticalPay::assemble(['rules' => [['kind' => 'its']], 'rounding' => 'nearest', 'base' => 250000, 'marital_status' => 'married', 'children' => 2, 'seniority_years' => 3]);

        foreach (['rules', 'rounding', 'factor', 'seniority_years', 'salary', 'labels', 'items', 'variables', 'family'] as $key) {
            $this->assertArrayHasKey($key, $in);
        }

        $this->assertSame(1.0, $in['factor']);
        $this->assertSame([], $in['variables']);
        $this->assertSame(['base' => 250000, 'supplement' => 0, 'mode' => 'gross', 'target_net' => null], $in['salary']);
        $this->assertSame(['marital_status' => 'married', 'children' => 2, 'infirm_adults' => 0], $in['family']);
        $this->assertSame(3, $in['seniority_years']);
    }

    public function test_assemble_guaranteed_net_uses_net_target_mode(): void
    {
        $in = HypotheticalPay::assemble(['base' => 250000, 'guaranteed_net' => 225000]);

        $this->assertSame('net_target', $in['salary']['mode']);
        $this->assertSame(225000, $in['salary']['target_net']);
    }

    public function test_assemble_clamps_negative_values(): void
    {
        $in = HypotheticalPay::assemble(['base' => -5, 'children' => -2, 'seniority_years' => -1, 'infirm_adults' => -1]);

        $this->assertSame(0, $in['salary']['base']);
        $this->assertSame(0, $in['family']['children']);
        $this->assertSame(0, $in['family']['infirm_adults']);
        $this->assertSame(0, $in['seniority_years']);
    }

    /** « employee_contributions » du moteur contient déjà l'ITS : les cotisations seules s'obtiennent par soustraction. */
    public function test_summarize_separates_social_contributions_from_the_tax(): void
    {
        $s = HypotheticalPay::summarize([
            'totals' => ['gross' => 300000, 'its' => 20000, 'employee_contributions' => 50000, 'net_contract' => 240000, 'employer_total' => 60000, 'employer_cost' => 360000],
            'supplement' => ['used' => 15000],
            'warnings' => ['Attention'],
        ], 250000);

        $this->assertSame(250000, $s['base']);
        $this->assertSame(300000, $s['gross']);
        $this->assertSame(30000, $s['social']);
        $this->assertSame(20000, $s['its']);
        $this->assertSame(50000, $s['employee_total']);
        $this->assertSame(240000, $s['net']);
        $this->assertSame(360000, $s['employer_cost']);
        $this->assertSame(4320000, $s['annual_cost']);
        $this->assertSame(15000, $s['supplement']);
        $this->assertSame(['Attention'], $s['warnings']);
        $this->assertNull($s['net_target']);
    }

    public function test_summarize_without_optional_parts(): void
    {
        $s = HypotheticalPay::summarize(['totals' => ['gross' => 100000, 'its' => 0, 'employee_contributions' => 0, 'net_contract' => 100000, 'employer_total' => 0, 'employer_cost' => 100000]]);

        $this->assertSame(0, $s['base']);
        $this->assertSame(0, $s['supplement']);
        $this->assertSame([], $s['warnings']);
        $this->assertSame(0, $s['social']);
    }
}
