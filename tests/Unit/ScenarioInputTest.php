<?php

namespace Tests\Unit;

use App\Services\Payroll\ScenarioInput;
use PHPUnit\Framework\TestCase;

class ScenarioInputTest extends TestCase
{
    public function test_blank_scenario_and_default_labels(): void
    {
        $b = ScenarioInput::blank('Scénario A');

        $this->assertSame('Scénario A', $b['label']);
        $this->assertSame('', $b['amount']);
        $this->assertSame('base', $b['from']);
        $this->assertSame('single', $b['marital_status']);
        $this->assertSame('Scénario A', ScenarioInput::defaultLabel(0));
        $this->assertSame('Scénario C', ScenarioInput::defaultLabel(2));
    }

    public function test_normalize_keeps_clean_values(): void
    {
        $n = ScenarioInput::normalize(['label' => 'Cadre marié', 'from' => 'base', 'amount' => '350000', 'marital_status' => 'married', 'children' => '2', 'infirm_adults' => '1', 'seniority' => '5', 'net' => '300000', 'pay_profile_id' => '4'], 'Scénario A');

        $this->assertSame(['label' => 'Cadre marié', 'from' => 'base', 'amount' => 350000, 'marital_status' => 'married', 'children' => 2, 'infirm_adults' => 1, 'seniority_years' => 5, 'net' => 300000, 'pay_profile_id' => 4], $n);
    }

    /** Tout ce qui vient du navigateur est du texte non fiable : chiffres seulement, bornes fixes, valeurs inconnues écartées. */
    public function test_normalize_cleans_untrusted_input(): void
    {
        $n = ScenarioInput::normalize(['label' => '   ', 'from' => 'autre', 'amount' => '350 000 FCFA', 'marital_status' => 'pacsé', 'children' => '99', 'infirm_adults' => '50', 'seniority' => '120', 'net' => '0', 'pay_profile_id' => ''], 'Scénario B');

        $this->assertSame('Scénario B', $n['label']);
        $this->assertSame(350000, $n['amount']);
        $this->assertSame('base', $n['from']);
        $this->assertNull($n['marital_status']);
        $this->assertSame(30, $n['children']);
        $this->assertSame(10, $n['infirm_adults']);
        $this->assertSame(50, $n['seniority_years']);
        $this->assertNull($n['net']);
        $this->assertNull($n['pay_profile_id']);
    }

    public function test_normalize_caps_huge_amounts_and_long_labels(): void
    {
        $n = ScenarioInput::normalize(['label' => str_repeat('é', 80), 'amount' => '99999999999999999999', 'net' => '99999999999999999999'], 'Scénario A');

        $this->assertSame(100000000000, $n['amount']);
        $this->assertSame(100000000000, $n['net']);
        $this->assertSame(40, mb_strlen($n['label']));
    }

    public function test_missing_keys_give_safe_defaults(): void
    {
        $n = ScenarioInput::normalize([], 'Scénario A');

        $this->assertSame(0, $n['amount']);
        $this->assertSame(0, $n['children']);
        $this->assertNull($n['net']);
        $this->assertNull($n['marital_status']);
    }

    public function test_problem_names_what_is_missing_for_each_starting_point(): void
    {
        $this->assertSame('Indiquez un salaire de base (ou choisissez un échelon).', ScenarioInput::problem(['from' => 'base', 'amount' => 0]));
        $this->assertSame('Indiquez le brut voulu.', ScenarioInput::problem(['from' => 'gross', 'amount' => 0]));
        $this->assertSame('Indiquez le net voulu.', ScenarioInput::problem(['from' => 'net', 'amount' => 0]));
        $this->assertNull(ScenarioInput::problem(['from' => 'net', 'amount' => 1]));
    }

    /** Le net visé garanti par un sursalaire n'a de sens qu'en partant du salaire de base : ailleurs il est écarté. */
    public function test_guaranteed_net_only_applies_when_starting_from_the_base(): void
    {
        $this->assertSame(225000, ScenarioInput::normalize(['from' => 'base', 'amount' => '250000', 'net' => '225000'], 'A')['net']);
        $this->assertNull(ScenarioInput::normalize(['from' => 'gross', 'amount' => '400000', 'net' => '225000'], 'A')['net']);
        $this->assertNull(ScenarioInput::normalize(['from' => 'net', 'amount' => '300000', 'net' => '225000'], 'A')['net']);
    }

    public function test_every_starting_point_is_a_known_one(): void
    {
        $this->assertSame(['base', 'gross', 'net'], array_keys(ScenarioInput::FROM));
        $this->assertSame('gross', ScenarioInput::normalize(['from' => 'gross', 'amount' => '1'], 'A')['from']);
        $this->assertSame('net', ScenarioInput::normalize(['from' => 'net', 'amount' => '1'], 'A')['from']);
    }
}
