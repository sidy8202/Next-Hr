<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Le moteur est testé sur les deux bulletins réels de février 2026. Les cotisations sont exactes au franc ;
 * l'ITS suit le texte du CGI et s'écarte de quelques dizaines de FCFA du logiciel de paie d'origine (écart déjà connu, voir PayrollLegalTest).
 */
class PayrollCalculatorTest extends TestCase
{
    private const BULLETIN_TOLERANCE = 60;

    private function rules(): array
    {
        return [
            ['code' => 'INPS_SAL', 'label' => 'INPS retraite', 'party' => 'employee', 'kind' => 'rate', 'rate' => 3.6, 'base' => 'cotisable', 'deductible_from_its' => true],
            ['code' => 'AMO_SAL', 'label' => 'AMO', 'party' => 'employee', 'kind' => 'rate', 'rate' => 3.06, 'base' => 'cotisable', 'deductible_from_its' => false],
            ['code' => 'ITS', 'label' => 'ITS', 'party' => 'employee', 'kind' => 'its', 'params' => IncomeTaxTest::params()],
            ['code' => 'INPS_EMP', 'label' => 'INPS employeur', 'party' => 'employer', 'kind' => 'rate', 'rate' => 16.4, 'base' => 'cotisable', 'deductible_from_its' => false],
            ['code' => 'AMO_EMP', 'label' => 'AMO patronale', 'party' => 'employer', 'kind' => 'rate', 'rate' => 3.5, 'base' => 'cotisable', 'deductible_from_its' => false],
            ['code' => 'CFE', 'label' => 'CFE', 'party' => 'employer', 'kind' => 'rate', 'rate' => 3.5, 'base' => 'brut', 'deductible_from_its' => false],
            ['code' => 'TL', 'label' => 'TL', 'party' => 'employer', 'kind' => 'rate', 'rate' => 1.0, 'base' => 'brut', 'deductible_from_its' => false],
        ];
    }

    private function item(array $o): array
    {
        return $o + [
            'item_id' => null, 'code' => 'X', 'label' => 'Rubrique', 'type' => 'gain', 'calc_type' => 'fixed', 'subject_to_social' => true, 'taxable' => true,
            'amount' => null, 'rate' => null, 'ref_item_id' => null, 'floor' => null, 'cap' => null, 'scale' => [],
        ];
    }

    private function variable(array $o): array
    {
        return $o + ['source' => 'variable', 'code' => null, 'label' => 'Variable', 'effect' => 'gross_add', 'quantity' => null, 'unit' => null, 'amount' => 0, 'item_id' => null, 'item_type' => null];
    }

    private function input(array $o = []): array
    {
        return $o + [
            'rules' => $this->rules(), 'rounding' => 'nearest', 'factor' => 1.0, 'seniority_years' => 0,
            'salary' => ['base' => 55000, 'supplement' => 211919, 'mode' => 'gross', 'target_net' => null],
            'labels' => [], 'items' => [], 'variables' => [],
            'family' => ['marital_status' => 'Célibataire', 'children' => 0, 'infirm_adults' => 0],
        ];
    }

    private function phone(): array
    {
        return $this->item(['code' => '100600', 'label' => 'Téléphone', 'type' => 'benefit_in_kind', 'amount' => 20000]);
    }

    /** Employé B, bulletin 2 (transit) : base 55 000 + sursalaire 211 919 + téléphone 20 000 (avantage en nature). */
    public function test_transit_reference_payslip(): void
    {
        $r = PayrollCalculator::compute($this->input(['items' => [$this->phone()]]));
        $t = $r['totals'];

        $this->assertSame(286919, $t['gross']);
        $this->assertSame(276590, $t['its_base']);
        $this->assertSame(47714, $t['its']);
        $this->assertSame(66823, $t['employee_contributions']);          // INPS 10 329 + AMO 8 780 + ITS 47 714
        $this->assertSame(220096, $t['net_before_other']);
        $this->assertSame(20000, $t['benefits']);                         // l'avantage en nature se retire du net
        $this->assertSame(200096, $t['net_to_pay']);
        $this->assertEqualsWithDelta(200049, $t['net_to_pay'], self::BULLETIN_TOLERANCE);   // net du bulletin
        $this->assertSame(70008, $t['employer_total']);                   // charges patronales du bulletin, exactes
        $this->assertSame(356927, $t['employer_cost']);
        $this->assertSame([], $r['warnings']);
    }

    /** Employé A, bulletin 1 (commerce) : base 44 364 + sursalaire 247 100 + indemnité spéciale 2 000 (cotisable, non imposable). */
    public function test_commerce_reference_payslip_with_a_non_taxable_allowance(): void
    {
        $special = $this->item(['code' => '100200', 'label' => 'Indemnité spéciale', 'amount' => 2000, 'taxable' => false]);
        $r = PayrollCalculator::compute($this->input(['salary' => ['base' => 44364, 'supplement' => 247100, 'mode' => 'gross', 'target_net' => null], 'items' => [$special]]));
        $t = $r['totals'];

        $this->assertSame(293464, $t['gross']);
        $this->assertSame(293464, $t['cotisable']);
        $this->assertSame(291464, $t['taxable_gross']);                   // les 2 000 ne sont pas imposables
        $this->assertSame(280899, $t['its_base']);
        $this->assertSame(48964, $t['its']);
        $this->assertSame(224955, $t['net_to_pay']);
        $this->assertEqualsWithDelta(225000, $t['net_to_pay'], self::BULLETIN_TOLERANCE);
        $this->assertSame(71605, $t['employer_total']);
    }

    public function test_a_partial_month_prorates_salary_and_fixed_items_but_not_variables(): void
    {
        $r = PayrollCalculator::compute($this->input(['factor' => 0.5, 'items' => [$this->phone()]]));
        $amounts = array_column($r['earnings'], 'amount');

        $this->assertSame([27500, 105960, 10000], $amounts);              // 211 919 × 0,5 = 105 959,5 arrondi
        $this->assertSame(143460, $r['totals']['gross']);
        $this->assertSame(10000, $r['totals']['benefits']);
        $this->assertSame(112409, $r['totals']['net_to_pay']);
    }

    public function test_an_unpaid_absence_reduces_the_gross_and_every_assiette(): void
    {
        $absence = $this->variable(['label' => 'Absence non rémunérée', 'effect' => 'gross_deduct', 'amount' => 26692, 'quantity' => 3, 'unit' => 'days']);
        $r = PayrollCalculator::compute($this->input(['items' => [$this->phone()], 'variables' => [$absence]]));
        $t = $r['totals'];

        $this->assertSame(260227, $t['gross']);                           // 286 919 − 26 692
        $this->assertSame(260227, $t['cotisable']);
        $this->assertSame(260227, $t['taxable_gross']);
        $this->assertSame(9368, collect($r['legal'])->firstWhere('code', 'INPS_SAL')['amount']);
        $this->assertSame(40252, $t['its']);
        $this->assertSame(182644, $t['net_to_pay']);
        $this->assertSame(63495, $t['employer_total']);
    }

    public function test_a_loan_installment_lowers_the_net_to_pay_but_not_the_contract_net(): void
    {
        $loan = $this->variable(['label' => 'Remboursement de prêt', 'effect' => 'net_deduct', 'amount' => 40000, 'item_type' => 'deduction_individual', 'source' => 'loan']);
        $r = PayrollCalculator::compute($this->input(['items' => [$this->phone()], 'variables' => [$loan]]));

        $this->assertSame(200096, $r['totals']['net_contract']);
        $this->assertSame(160096, $r['totals']['net_to_pay']);
        $this->assertSame(40000, $r['totals']['individual']);
        $this->assertSame(286919, $r['totals']['gross']);                 // un prêt ne change ni le brut ni les cotisations
    }

    public function test_internal_deductions_come_off_the_net(): void
    {
        $insurance = $this->item(['code' => '300100', 'label' => 'Assurance complémentaire', 'type' => 'deduction_internal', 'amount' => 5000, 'subject_to_social' => false, 'taxable' => false]);
        $r = PayrollCalculator::compute($this->input(['items' => [$this->phone(), $insurance]]));

        $this->assertSame(5000, $r['totals']['internal']);
        $this->assertSame(195096, $r['totals']['net_contract']);
        $this->assertSame(286919, $r['totals']['gross']);
    }

    public function test_a_gross_variable_can_be_excluded_from_the_assiettes(): void
    {
        $bonus = $this->variable(['label' => 'Prime exceptionnelle', 'effect' => 'gross_add', 'amount' => 10000, 'subject_to_social' => false, 'taxable' => false]);
        $r = PayrollCalculator::compute($this->input(['variables' => [$bonus]]));

        $this->assertSame(276919, $r['totals']['gross']);
        $this->assertSame(266919, $r['totals']['cotisable']);
        $this->assertSame(266919, $r['totals']['taxable_gross']);
    }

    public function test_a_benefit_in_kind_variable_is_added_to_the_gross_then_removed_from_the_net(): void
    {
        $car = $this->variable(['label' => 'Véhicule', 'effect' => 'gross_add', 'amount' => 30000, 'item_type' => 'benefit_in_kind']);
        $r = PayrollCalculator::compute($this->input(['variables' => [$car]]));

        $this->assertSame(296919, $r['totals']['gross']);
        $this->assertSame(30000, $r['totals']['benefits']);
    }

    public function test_percentage_items(): void
    {
        $housing = $this->item(['item_id' => 7, 'code' => '100700', 'label' => 'Prime de logement', 'calc_type' => 'percent_base', 'rate' => 10.0]);
        $linked = $this->item(['item_id' => 8, 'code' => '100800', 'label' => 'Complément', 'calc_type' => 'percent_item', 'rate' => 20.0, 'ref_item_id' => 7]);
        $gross = $this->item(['item_id' => 9, 'code' => '300200', 'label' => 'Cotisation mutuelle', 'type' => 'deduction_internal', 'calc_type' => 'percent_gross', 'rate' => 2.0, 'subject_to_social' => false, 'taxable' => false]);

        $r = PayrollCalculator::compute($this->input(['items' => [$linked, $housing, $gross]]));   // l'ordre fourni n'a pas d'importance
        $byCode = collect(array_merge($r['earnings'], $r['net_deductions']))->keyBy('code');

        $this->assertSame(5500, $byCode['100700']['amount']);             // 10 % de 55 000
        $this->assertSame(1100, $byCode['100800']['amount']);             // 20 % de la prime de logement
        $this->assertSame(273519, $r['totals']['gross']);                 // 266 919 + 5 500 + 1 100
        $this->assertSame(5470, $byCode['300200']['amount']);             // 2 % du brut de 273 519 = 5 470,38
    }

    public function test_seniority_scale_picks_the_highest_reached_step(): void
    {
        $scale = [[0, 0.0], [3, 5.0], [5, 10.0]];
        $item = fn () => $this->item(['code' => '100300', 'label' => "Prime d'ancienneté", 'calc_type' => 'scale', 'scale' => $scale]);

        $amount = fn (int $years) => PayrollCalculator::compute($this->input(['seniority_years' => $years, 'items' => [$item()]]))['earnings'][2]['amount'] ?? 0;

        $this->assertSame(0, $amount(2));
        $this->assertSame(2750, $amount(4));                              // 5 % de 55 000
        $this->assertSame(5500, $amount(6));                              // 10 % de 55 000
    }

    public function test_floor_and_cap_apply_to_computed_values(): void
    {
        $low = $this->item(['code' => 'A', 'label' => 'Petite prime', 'amount' => 100, 'floor' => 500]);
        $high = $this->item(['code' => 'B', 'label' => 'Grosse prime', 'amount' => 9000, 'cap' => 5000]);

        $r = PayrollCalculator::compute($this->input(['items' => [$low, $high]]));
        $byCode = collect($r['earnings'])->keyBy('code');

        $this->assertSame(500, $byCode['A']['amount']);
        $this->assertSame(5000, $byCode['B']['amount']);
    }

    public function test_an_item_without_a_value_counts_zero_and_warns(): void
    {
        $empty = $this->item(['code' => '100400', 'label' => 'Indemnité de transport', 'amount' => null]);
        $r = PayrollCalculator::compute($this->input(['items' => [$empty]]));

        $this->assertSame(266919, $r['totals']['gross']);
        $this->assertCount(1, $r['warnings']);
        $this->assertStringContainsString('Indemnité de transport', $r['warnings'][0]);
    }

    public function test_a_variable_without_an_amount_warns(): void
    {
        $r = PayrollCalculator::compute($this->input(['variables' => [$this->variable(['label' => 'Absence', 'effect' => 'gross_deduct', 'amount' => null])]]));

        $this->assertStringContainsString('Absence', $r['warnings'][0]);
        $this->assertSame(266919, $r['totals']['gross']);
    }

    public function test_family_reduction_applies_to_the_its(): void
    {
        $r = PayrollCalculator::compute($this->input([
            'salary' => ['base' => 500000, 'supplement' => 0, 'mode' => 'gross', 'target_net' => null],
            'family' => ['marital_status' => 'Marié', 'children' => 2, 'infirm_adults' => 0],
        ]));

        $this->assertSame(99477, $r['totals']['its']);                    // réduction de 15 % (10 % marié + 2 × 2,5 %)
        $this->assertSame(367223, $r['totals']['net_to_pay']);
    }

    /** Net garanti : on cherche le plus petit sursalaire dont le net atteint le net convenu. */
    public function test_net_target_recomputes_the_supplement(): void
    {
        $in = $this->input(['salary' => ['base' => 100000, 'supplement' => 0, 'mode' => 'net_target', 'target_net' => 250000]]);
        $r = PayrollCalculator::compute($in);

        $this->assertSame(235624, $r['supplement']['used']);
        $this->assertTrue($r['supplement']['recomputed']);
        $this->assertSame(250000, $r['totals']['net_contract']);
        $this->assertSame(250000, $r['totals']['net_to_pay']);
        $this->assertSame(0, $r['net_target']['gap']);
        $this->assertTrue($r['net_target']['reached']);

        // un franc de moins de sursalaire ne suffit pas
        $below = PayrollCalculator::compute($this->input(['salary' => ['base' => 100000, 'supplement' => 235623, 'mode' => 'gross', 'target_net' => null]]));
        $this->assertSame(249999, $below['totals']['net_contract']);
    }

    public function test_explain_its_matches_the_computed_its(): void
    {
        $e = PayrollCalculator::explainIts($this->rules(), 276590, ['marital_status' => 'Célibataire', 'children' => 0, 'infirm_adults' => 0]);

        $this->assertSame(3319080, $e['annual_base']);
        $this->assertSame(638954.41, $e['gross_tax']);
        $this->assertSame(0.0, $e['family_percent']);
        $this->assertSame(66381.6, $e['rebate']);
        $this->assertSame(572572.81, $e['annual_its']);
        $this->assertSame(47714.4, $e['monthly_its']);
        $this->assertNull(PayrollCalculator::explainIts([], 1000, []));
    }
}
