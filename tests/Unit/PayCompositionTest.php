<?php

namespace Tests\Unit;

use App\Services\Payroll\PayComposition;
use PHPUnit\Framework\TestCase;

class PayCompositionTest extends TestCase
{
    private function item(int $id, string $code, string $label, string $type, array $extra = []): array
    {
        return $extra + [
            'id' => $id, 'code' => $code, 'label' => $label, 'type' => $type, 'calc_type' => 'fixed',
            'subject_to_social' => true, 'taxable' => true, 'default_amount' => null, 'default_rate' => null,
            'ref_item_id' => null, 'floor_amount' => null, 'cap_amount' => null, 'show_on_payslip' => true, 'sort' => 0,
        ];
    }

    /** Employé A (bulletin 1, commerce) : le modèle apporte l'indemnité spéciale, soumise aux cotisations mais non imposable. */
    public function test_employee_a_gets_the_special_allowance_from_the_profile(): void
    {
        $special = $this->item(1, '100200', 'Indemnité spéciale', 'gain', ['taxable' => false, 'default_amount' => 2000]);

        $lines = PayComposition::merge([PayComposition::makeLine($special)], []);

        $this->assertCount(1, $lines);
        $this->assertSame(2000, $lines[0]['amount']);
        $this->assertTrue($lines[0]['subject_to_social']);
        $this->assertFalse($lines[0]['taxable']);
        $this->assertSame('profile', $lines[0]['source']);
    }

    /** Employé B (bulletin 2, transit) : le téléphone du modèle est remplacé par la valeur attribuée à l'employé. */
    public function test_employee_b_personal_phone_allowance_overrides_the_profile(): void
    {
        $phone = $this->item(2, '100600', 'Téléphone', 'benefit_in_kind');

        $profile = [PayComposition::makeLine($phone)];                       // modèle : pas de valeur
        $employee = [PayComposition::makeLine($phone, 20000)];               // prime attribuée : 20 000

        $lines = PayComposition::merge($profile, $employee);

        $this->assertCount(1, $lines);
        $this->assertSame(20000, $lines[0]['amount']);
        $this->assertSame('benefit_in_kind', $lines[0]['type']);
        $this->assertSame('employee_override', $lines[0]['source']);
    }

    public function test_an_employee_only_allowance_is_added_after_profile_lines_in_order(): void
    {
        $special = $this->item(1, '100200', 'Indemnité spéciale', 'gain', ['default_amount' => 2000, 'sort' => 20]);
        $transport = $this->item(3, '100400', 'Transport', 'gain', ['sort' => 10]);

        $lines = PayComposition::merge(
            [PayComposition::makeLine($special)],
            [PayComposition::makeLine($transport, 15000)]
        );

        $this->assertSame(['100400', '100200'], array_column($lines, 'code'));
        $this->assertSame('employee', $lines[0]['source']);
        $this->assertSame('profile', $lines[1]['source']);
    }

    public function test_profile_value_replaces_the_item_default(): void
    {
        $item = $this->item(4, '100500', 'Indemnité de fonction', 'gain', ['default_amount' => 10000]);

        $this->assertSame(10000, PayComposition::makeLine($item)['amount']);
        $this->assertSame(15000, PayComposition::makeLine($item, 15000)['amount']);
    }

    public function test_percentage_items_keep_their_rate(): void
    {
        $item = $this->item(5, '300100', 'Assurance complémentaire', 'deduction_internal', ['calc_type' => 'percent_gross', 'default_rate' => 1.5]);

        $line = PayComposition::makeLine($item);

        $this->assertSame(1.5, $line['rate']);
        $this->assertNull($line['amount']);
        $this->assertSame(2.0, PayComposition::makeLine($item, null, 2.0)['rate']);
    }

    public function test_is_active_on_handles_open_ended_periods(): void
    {
        $this->assertTrue(PayComposition::isActiveOn('2026-01-01', null, '2026-02-28'));
        $this->assertTrue(PayComposition::isActiveOn('2026-01-01', '2026-02-28', '2026-02-28'));
        $this->assertFalse(PayComposition::isActiveOn('2026-01-01', '2026-02-27', '2026-02-28'));
        $this->assertFalse(PayComposition::isActiveOn('2026-03-01', null, '2026-02-28'));
    }

    public function test_overlaps_detects_conflicting_periods(): void
    {
        $this->assertTrue(PayComposition::overlaps('2026-01-01', null, '2026-06-01', '2026-06-30'));
        $this->assertTrue(PayComposition::overlaps('2026-01-01', '2026-03-31', '2026-03-31', null));
        $this->assertFalse(PayComposition::overlaps('2026-01-01', '2026-03-31', '2026-04-01', null));
    }
}
