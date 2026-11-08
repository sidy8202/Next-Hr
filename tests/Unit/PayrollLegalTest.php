<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollLegal;
use PHPUnit\Framework\TestCase;

/**
 * Cas de référence tirés de deux bulletins réels de février 2026 (deux entreprises, deux conventions).
 * Cotisations : exactes au franc. ITS : le calcul suit le texte du CGI ; les deux bulletins s'en écartent de quelques dizaines
 * de FCFA (arrondis du logiciel de paie ?). La tolérance ci-dessous est volontairement visible : à resserrer dès que le comptable
 * aura fourni le détail de calcul de l'ITS.
 */
class PayrollLegalTest extends TestCase
{
    private const ITS_TOLERANCE = 60;

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

    private function line(array $result, string $code): array
    {
        return collect($result['lines'])->firstWhere('code', $code);
    }

    /** Bulletin n°1 (convention du commerce) : brut 293 464 dont 2 000 d'indemnité non imposable ; célibataire, 0 enfant. */
    public function test_reference_payslip_commerce(): void
    {
        $r = PayrollLegal::compute($this->rules(), ['gross' => 293464, 'taxable_gross' => 291464, 'marital_status' => 'Célibataire', 'children' => 0]);

        $this->assertSame(8980, $this->line($r, 'AMO_SAL')['amount']);
        $this->assertSame(10565, $this->line($r, 'INPS_SAL')['amount']);
        $this->assertSame(280899, $r['its_base']);                         // base de l'ITS exacte
        $this->assertEqualsWithDelta(48919, $r['its'], self::ITS_TOLERANCE);
        $this->assertEqualsWithDelta(225000, $r['net_before_other'], self::ITS_TOLERANCE);
    }

    /** Bulletin n°2 (convention du transit) : brut 286 919 ; parts patronales exactes au franc. */
    public function test_reference_payslip_transit(): void
    {
        $r = PayrollLegal::compute($this->rules(), ['gross' => 286919, 'marital_status' => 'Célibataire', 'children' => 0]);

        $this->assertSame(10329, $this->line($r, 'INPS_SAL')['amount']);
        $this->assertSame(8780, $this->line($r, 'AMO_SAL')['amount']);
        $this->assertSame(10042, $this->line($r, 'CFE')['amount']);
        $this->assertSame(2869, $this->line($r, 'TL')['amount']);
        $this->assertSame(47055, $this->line($r, 'INPS_EMP')['amount']);
        $this->assertSame(10042, $this->line($r, 'AMO_EMP')['amount']);
        $this->assertSame(70008, $r['employer_total']);                    // charges patronales du bulletin
        $this->assertEqualsWithDelta(47761, $r['its'], self::ITS_TOLERANCE);
    }

    public function test_a_ceiling_limits_the_base(): void
    {
        $rules = [['code' => 'X', 'label' => 'X', 'party' => 'employee', 'kind' => 'rate', 'rate' => 5.0, 'base' => 'cotisable', 'ceiling_monthly' => 100000, 'deductible_from_its' => false]];
        $r = PayrollLegal::compute($rules, ['gross' => 500000]);

        $this->assertSame(5000, $r['lines'][0]['amount']);
        $this->assertSame(495000, $r['net_before_other']);
    }

    public function test_employer_cost_adds_employer_charges_to_the_gross(): void
    {
        $r = PayrollLegal::compute($this->rules(), ['gross' => 286919]);

        $this->assertSame(286919 + 70008, $r['employer_cost']);
    }
}
