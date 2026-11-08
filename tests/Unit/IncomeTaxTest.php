<?php

namespace Tests\Unit;

use App\Services\Payroll\IncomeTax;
use PHPUnit\Framework\TestCase;

class IncomeTaxTest extends TestCase
{
    /** Barème de l'article 10 du CGI (loi 2016-10). */
    public static function params(): array
    {
        return [
            'brackets' => [[0, 330000, 0], [330000, 578400, 5], [578400, 1176400, 12], [1176400, 1789733, 18], [1789733, 2384195, 26], [2384195, 3494130, 31], [3494130, null, 37]],
            'family' => ['married' => 10, 'per_child' => 2.5, 'max_children' => 10, 'infirm_adult' => 10],
            'rebate_points' => 2,
        ];
    }

    public function test_income_below_the_first_threshold_pays_nothing(): void
    {
        $this->assertSame(0.0, IncomeTax::grossAnnual(300000, self::params()['brackets']));
        $this->assertSame(0.0, IncomeTax::annual(300000, self::params(), false, 0));
    }

    public function test_gross_tax_is_progressive(): void
    {
        // 330 000 → 578 400 à 5 % puis 578 400 → 1 000 000 à 12 %
        $expected = 248400 * 0.05 + (1000000 - 578400) * 0.12;
        $this->assertEqualsWithDelta($expected, IncomeTax::grossAnnual(1000000, self::params()['brackets']), 0.01);
    }

    public function test_family_reduction_percentages(): void
    {
        $f = self::params()['family'];

        $this->assertSame(0.0, IncomeTax::familyPercent(false, 0, $f));
        $this->assertSame(10.0, IncomeTax::familyPercent(true, 0, $f));
        $this->assertSame(17.5, IncomeTax::familyPercent(true, 3, $f));
        $this->assertSame(25.0, IncomeTax::familyPercent(false, 10, $f));
        $this->assertSame(25.0, IncomeTax::familyPercent(false, 14, $f));   // plafonné au dixième enfant
        $this->assertSame(20.0, IncomeTax::familyPercent(true, 0, $f, 1));  // enfant majeur infirme
    }

    public function test_marital_status_recognition(): void
    {
        foreach (['Marié', 'mariée', 'Married', 'marie'] as $s) { $this->assertTrue(IncomeTax::isMarried($s), $s); }
        foreach (['Célibataire', 'single', 'Divorcé', 'Veuve', '', null] as $s) { $this->assertFalse(IncomeTax::isMarried($s), (string) $s); }
    }

    public function test_family_reduction_lowers_the_tax(): void
    {
        $single = IncomeTax::annual(3000000, self::params(), false, 0);
        $married = IncomeTax::annual(3000000, self::params(), true, 0);
        $family = IncomeTax::annual(3000000, self::params(), true, 3);

        $this->assertLessThan($single, $married);
        $this->assertLessThan($married, $family);
    }

    public function test_two_point_rebate_never_makes_the_tax_negative(): void
    {
        $this->assertGreaterThanOrEqual(0.0, IncomeTax::annual(400000, self::params(), true, 10));
    }

    public function test_rounding_modes(): void
    {
        $this->assertSame(11, IncomeTax::round(10.5, 'nearest'));
        $this->assertSame(10, IncomeTax::round(10.9, 'floor'));
        $this->assertSame(11, IncomeTax::round(10.1, 'ceil'));
    }
}
