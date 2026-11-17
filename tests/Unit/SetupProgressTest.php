<?php

namespace Tests\Unit;

use App\Services\Payroll\SetupProgress;
use App\Services\Payroll\SetupSteps;
use PHPUnit\Framework\TestCase;

class SetupProgressTest extends TestCase
{
    /** Sahel Négoce : 12 employés, 9 salaires saisis, aucune affectation. */
    protected function sahel(array $override = []): array
    {
        return $override + [
            'settings_confirmed' => true, 'variable_settings_confirmed' => false,
            'grids' => 0, 'system_items' => 12, 'profiles' => 2,
            'employees' => 12, 'employees_with_salary' => 9, 'employees_with_profile' => 0,
            'default_templates' => 0, 'simulation_done' => false, 'periods_calculated' => 0, 'skipped' => [],
        ];
    }

    protected function getCustomStatus(array $result, string $key): string
    {
        foreach ($result['steps'] as $s) {
            if ($s['key'] === $key) {
                return $s['status'];
            }
        }

        $this->fail("Étape $key introuvable");
    }

    public function test_sahel_negoce_starts_at_43_percent_with_4_steps_left(): void
    {
        $r = SetupProgress::evaluate($this->sahel());

        $this->assertSame(7, $r['required_total']);
        $this->assertSame(3, $r['required_done']);
        $this->assertSame(4, $r['remaining']);
        $this->assertSame(43, $r['percent']);
        $this->assertFalse($r['complete']);
        $this->assertSame('salaries', $r['next']);
        $this->assertSame(SetupProgress::PARTIAL, $this->status($r, 'salaries'));
        $this->assertSame(SetupProgress::TODO, $this->status($r, 'assignments'));
        $this->assertSame(SetupProgress::OPTIONAL, $this->status($r, 'variable_settings'));
        $this->assertSame(SetupProgress::OPTIONAL, $this->status($r, 'grid'));
        $this->assertSame(SetupProgress::OPTIONAL, $this->status($r, 'template'));
    }

    public function test_partial_step_shows_the_count(): void
    {
        $r = SetupProgress::evaluate($this->sahel());
        $salaries = array_values(array_filter($r['steps'], fn ($s) => $s['key'] === 'salaries'))[0];

        $this->assertSame('9 sur 12 ont un salaire en vigueur.', $salaries['detail']);
    }

    public function test_after_salaries_and_assignments_are_complete_it_reaches_71_percent(): void
    {
        $r = SetupProgress::evaluate($this->sahel(['employees_with_salary' => 12, 'employees_with_profile' => 12]));

        $this->assertSame(5, $r['required_done']);
        $this->assertSame(2, $r['remaining']);
        $this->assertSame(71, $r['percent']);
        $this->assertSame('simulation', $r['next']);
    }

    public function test_everything_done_is_complete_even_with_optional_steps_open(): void
    {
        $r = SetupProgress::evaluate($this->sahel([
            'employees_with_salary' => 12, 'employees_with_profile' => 12, 'simulation_done' => true, 'periods_calculated' => 1,
        ]));

        $this->assertTrue($r['complete']);
        $this->assertSame(100, $r['percent']);
        $this->assertSame(0, $r['remaining']);
        $this->assertNull($r['next']);
        $this->assertSame(SetupProgress::OPTIONAL, $this->status($r, 'grid'));
    }

    public function test_percent_rounds_to_the_nearest_integer(): void
    {
        // 1/7 = 14,3 · 2/7 = 28,6 · 6/7 = 85,7
        $one = SetupProgress::evaluate(['settings_confirmed' => true]);
        $this->assertSame(1, $one['required_done']);
        $this->assertSame(14, $one['percent']);

        $two = SetupProgress::evaluate(['settings_confirmed' => true, 'system_items' => 1]);
        $this->assertSame(29, $two['percent']);

        $six = SetupProgress::evaluate($this->sahel([
            'employees_with_salary' => 12, 'employees_with_profile' => 12, 'simulation_done' => true,
        ]));
        $this->assertSame(6, $six['required_done']);
        $this->assertSame(86, $six['percent']);
    }

    public function test_a_skipped_optional_step_is_marked_skipped(): void
    {
        $r = SetupProgress::evaluate($this->sahel(['skipped' => ['grid', 'template']]));

        $this->assertSame(SetupProgress::SKIPPED, $this->status($r, 'grid'));
        $this->assertSame(SetupProgress::SKIPPED, $this->status($r, 'template'));
        $this->assertSame(43, $r['percent']);
    }

    public function test_a_required_step_cannot_be_skipped(): void
    {
        $r = SetupProgress::evaluate($this->sahel(['system_items' => 0, 'skipped' => ['items', 'settings']]));

        $this->assertSame(SetupProgress::TODO, $this->status($r, 'items'));
    }

    public function test_a_grid_makes_the_grid_step_done(): void
    {
        $r = SetupProgress::evaluate($this->sahel(['grids' => 2]));
        $grid = array_values(array_filter($r['steps'], fn ($s) => $s['key'] === 'grid'))[0];

        $this->assertSame(SetupProgress::DONE, $grid['status']);
        $this->assertSame('2 grilles actives.', $grid['detail']);
    }

    public function test_without_employees_salaries_and_assignments_explain_what_to_do_first(): void
    {
        $r = SetupProgress::evaluate(['employees' => 0, 'profiles' => 1]);
        $details = array_column($r['steps'], 'detail', 'key');

        $this->assertSame(SetupProgress::TODO, $this->status($r, 'salaries'));
        $this->assertStringContainsString('ajoutez d\'abord vos employés', $details['salaries']);
        $this->assertStringContainsString('ajoutez d\'abord vos employés', $details['assignments']);
        $this->assertSame('Renseignez d\'abord au moins un salaire.', $details['simulation']);
    }

    public function test_assignments_ask_for_a_profile_first_when_none_exists(): void
    {
        $r = SetupProgress::evaluate(['employees' => 5, 'profiles' => 0, 'employees_with_profile' => 0]);
        $details = array_column($r['steps'], 'detail', 'key');

        $this->assertSame('Créez d\'abord au moins un modèle de traitement.', $details['assignments']);
    }

    public function test_the_result_is_the_same_for_the_same_facts(): void
    {
        $this->assertSame(SetupProgress::evaluate($this->sahel()), SetupProgress::evaluate($this->sahel()));
    }

    public function test_legal_state_missing_when_no_rule(): void
    {
        $this->assertSame(['state' => 'missing', 'total' => 0, 'validated' => 0], SetupProgress::legalState([]));
    }

    public function test_legal_state_to_validate_counts_unvalidated_rules(): void
    {
        $rules = [['status' => 'validated'], ['status' => 'to_validate'], ['status' => 'to_validate']];

        $this->assertSame(['state' => 'to_validate', 'total' => 3, 'validated' => 1], SetupProgress::legalState($rules));
    }

    public function test_legal_state_validated_when_all_rules_are(): void
    {
        $this->assertSame(['state' => 'validated', 'total' => 2, 'validated' => 2], SetupProgress::legalState([['status' => 'validated'], ['status' => 'validated']]));
    }

    public function test_steps_definitions_are_consistent(): void
    {
        $all = SetupSteps::all();
        $keys = SetupSteps::keys();

        $this->assertCount(10, $all);
        $this->assertSame($keys, array_values(array_unique($keys)));
        $this->assertCount(7, array_filter($all, fn ($s) => $s['required']));

        foreach ($all as $s) {
            $this->assertNotSame('', $s['route']);
            $this->assertNotSame('', $s['resource']);
            $this->assertNotSame('', $s['example']);
        }

        // seules les deux étapes de valeurs de départ se confirment à la main
        $this->assertSame(['settings', 'variable_settings'], array_values(array_map(fn ($s) => $s['key'], array_filter($all, fn ($s) => $s['confirmable']))));
    }
}
