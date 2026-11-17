<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollUiMode;
use PHPUnit\Framework\TestCase;

class PayrollUiModeTest extends TestCase
{
    protected array $all = [
        'fixed' => 'Montant fixe', 'percent_base' => '% du salaire de base', 'percent_gross' => '% du brut',
        'percent_item' => "% d'une autre rubrique", 'qty_rate' => 'Quantité × taux', 'scale' => "Barème d'ancienneté", 'system' => 'Calcul du moteur',
    ];

    public function test_normalize_accepts_only_the_two_modes(): void
    {
        $this->assertSame('simple', PayrollUiMode::normalize('simple'));
        $this->assertSame('advanced', PayrollUiMode::normalize('advanced'));
        $this->assertNull(PayrollUiMode::normalize('expert'));
        $this->assertNull(PayrollUiMode::normalize(''));
        $this->assertNull(PayrollUiMode::normalize(null));
    }

    public function test_a_stored_valid_mode_always_wins(): void
    {
        $this->assertSame('simple', PayrollUiMode::resolve('simple', true));
        $this->assertSame('advanced', PayrollUiMode::resolve('advanced', false));
    }

    public function test_without_a_stored_mode_the_company_keeps_what_it_already_uses(): void
    {
        $this->assertSame('advanced', PayrollUiMode::resolve(null, true));
        $this->assertSame('simple', PayrollUiMode::resolve(null, false));
        $this->assertSame('simple', PayrollUiMode::resolve('bizarre', false));
        $this->assertSame('advanced', PayrollUiMode::resolve('bizarre', true));
    }

    public function test_advanced_mode_shows_every_calc_type(): void
    {
        $this->assertSame($this->all, PayrollUiMode::calcTypes($this->all, false, 'fixed'));
    }

    public function test_simple_mode_keeps_three_calc_types_and_the_engine_one(): void
    {
        $types = PayrollUiMode::calcTypes($this->all, true, 'fixed');

        $this->assertSame(['fixed', 'percent_base', 'percent_gross', 'system'], array_keys($types));
        $this->assertSame('Montant fixe', $types['fixed']);
    }

    public function test_simple_mode_never_hides_the_calc_type_an_item_already_uses(): void
    {
        $this->assertContains('scale', array_keys(PayrollUiMode::calcTypes($this->all, true, 'scale')));
        $this->assertContains('percent_item', array_keys(PayrollUiMode::calcTypes($this->all, true, 'percent_item')));
        $this->assertContains('qty_rate', array_keys(PayrollUiMode::calcTypes($this->all, true, 'qty_rate')));
        $this->assertNotContains('scale', array_keys(PayrollUiMode::calcTypes($this->all, true, 'fixed')));
        $this->assertNotContains('scale', array_keys(PayrollUiMode::calcTypes($this->all, true, null)));
    }

    public function test_limits_are_hidden_in_simple_mode_unless_already_set(): void
    {
        $this->assertFalse(PayrollUiMode::showLimits(true, false));
        $this->assertTrue(PayrollUiMode::showLimits(true, true));
        $this->assertTrue(PayrollUiMode::showLimits(false, false));
        $this->assertTrue(PayrollUiMode::showLimits(false, true));
    }

    public function test_net_target_option_is_hidden_in_simple_mode_unless_already_used(): void
    {
        $this->assertFalse(PayrollUiMode::showNetTarget(true, 'gross'));
        $this->assertTrue(PayrollUiMode::showNetTarget(true, 'net_target'));
        $this->assertTrue(PayrollUiMode::showNetTarget(false, 'gross'));
        $this->assertTrue(PayrollUiMode::showNetTarget(false, 'net_target'));
    }

    public function test_every_hidden_calc_type_is_a_known_advanced_one(): void
    {
        $hidden = array_diff(array_keys($this->all), array_keys(PayrollUiMode::calcTypes($this->all, true, null)));

        $expected = PayrollUiMode::ADVANCED_CALC_TYPES;
        $hidden = array_values($hidden);
        sort($expected);
        sort($hidden);
        $this->assertSame($expected, $hidden);
            }
}
