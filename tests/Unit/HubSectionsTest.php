<?php

namespace Tests\Unit;

use App\Services\Payroll\HubSections;
use App\Services\Payroll\SetupSteps;
use PHPUnit\Framework\TestCase;

class HubSectionsTest extends TestCase
{
    public function test_there_are_three_hubs_with_the_expected_sections(): void
    {
        $all = HubSections::all();

        $this->assertSame(['payroll.month', 'payroll.people', 'payroll.config'], array_keys($all));
        $this->assertSame(['month', 'variables', 'loans', 'simulation'], array_column($all['payroll.month'], 'key'));
        $this->assertSame(['salaries', 'assignments', 'bonuses'], array_column($all['payroll.people'], 'key'));
        $this->assertSame(['settings', 'packages', 'items', 'profiles', 'grids', 'templates', 'variable-settings'], array_column($all['payroll.config'], 'key'));
    }

    public function test_section_keys_are_unique_in_a_hub_and_valid_as_html_anchors(): void
    {
        foreach (HubSections::all() as $route => $sections) {
            $keys = array_column($sections, 'key');

            $this->assertSame($keys, array_values(array_unique($keys)), $route);

            foreach ($keys as $key) {
                $this->assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $key);
            }
        }
    }

    public function test_every_section_has_a_title_a_hint_a_resource_and_a_unique_livewire_alias(): void
    {
        $aliases = [];

        foreach (HubSections::all() as $sections) {
            foreach ($sections as $s) {
                $this->assertNotSame('', $s['title']);
                $this->assertNotSame('', $s['hint']);
                $this->assertNotSame('', $s['resource']);
                $this->assertMatchesRegularExpression('/^payroll\.[a-z0-9]+(-[a-z0-9]+)*$/', $s['alias']);
                $aliases[] = $s['alias'];
            }
        }

        $this->assertSame($aliases, array_values(array_unique($aliases)));
    }

    public function test_every_old_route_redirects_to_an_existing_section(): void
    {
        $redirects = HubSections::redirects();

        $this->assertCount(13, $redirects);

        foreach ($redirects as $old => $target) {
            $this->assertTrue(HubSections::hasSection($target['route'], $target['anchor']), "$old -> {$target['route']}#{$target['anchor']}");
        }

        // la route du pôle « paie du mois » est celle de l'ancien écran : elle ne redirige pas vers elle-même
        $this->assertArrayNotHasKey('payroll.month', $redirects);
        $this->assertArrayNotHasKey('payroll.people', $redirects);
        $this->assertArrayNotHasKey('payroll.config', $redirects);
    }

    public function test_every_old_menu_route_is_covered_by_a_redirect(): void
    {
        $old = ['payroll.variables', 'payroll.loans', 'payroll.simulation', 'payroll.salaries', 'payroll.assignments', 'payroll.bonuses', 'payroll.settings', 'payroll.grids', 'payroll.items', 'payroll.profiles', 'payroll.templates', 'payroll.variable-settings', 'payroll.packages'];

        foreach ($old as $name) {
            $this->assertNotNull(HubSections::redirectFor($name), $name);
        }

        $this->assertNull(HubSections::redirectFor('payroll.unknown'));
        $this->assertNull(HubSections::redirectFor(''));
    }

    public function test_the_assistant_steps_point_to_existing_sections(): void
    {
        foreach (SetupSteps::all() as $step) {
            $this->assertArrayHasKey('anchor', $step, $step['key']);
            $this->assertTrue(HubSections::hasSection($step['route'], $step['anchor']), $step['key'] . ' -> ' . $step['route'] . '#' . $step['anchor']);

            if (isset($step['extra_route'])) {
                $this->assertTrue(HubSections::hasSection($step['extra_route'], $step['extra_anchor']), $step['key'] . ' (bouton secondaire)');
            }
        }
    }

    public function test_has_section_is_false_for_unknown_hubs_and_keys(): void
    {
        $this->assertFalse(HubSections::hasSection('payroll.nope', 'items'));
        $this->assertFalse(HubSections::hasSection('payroll.config', 'salaries'));
    }
}
