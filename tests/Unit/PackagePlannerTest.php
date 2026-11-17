<?php

namespace Tests\Unit;

use App\Services\Payroll\PackagePlanner;
use App\Services\Payroll\PackageSchema;
use App\Services\Payroll\PackageStarterData;
use PHPUnit\Framework\TestCase;

class PackagePlannerTest extends TestCase
{
    protected function company(array $o = []): array
    {
        return $o + ['item_codes' => [], 'profile_names' => [], 'grid_names' => [], 'template_names' => [], 'has_default_template' => false, 'convention_name' => null];
    }

    /** Paquet de test : 14 rubriques R01..R14, 2 modèles, 1 grille de 6 catégories, un modèle de bulletin, une convention. */
    protected function big(): array
    {
        $items = [];

        for ($i = 1; $i <= 14; $i++) {
            $items[] = ['code' => sprintf('R%02d', $i), 'label' => "Rubrique $i", 'type' => 'gain', 'calc_type' => 'fixed'];
        }

        $grades = [];

        foreach (['E', 'D', 'C', 'B', 'A', 'M2'] as $code) {
            $grades[] = ['code' => $code, 'steps' => [['label' => 'Échelon 1', 'amount' => 100000]]];
        }

        return [
            'items' => $items,
            'profiles' => [
                ['name' => 'Employés', 'items' => [['code' => 'R01'], ['code' => 'R02']]],
                ['name' => 'Cadres', 'items' => [['code' => 'R01'], ['code' => 'R03']]],
            ],
            'grids' => [['name' => 'Grille type', 'grades' => $grades]],
            'template' => ['name' => 'Standard'],
            'settings' => ['convention_name' => 'Convention X'],
        ];
    }

    protected function commerce(): array
    {
        foreach (PackageStarterData::all() as $p) {
            if ($p['code'] === 'commerce') {
                return $p['payload'];
            }
        }

        $this->fail('Paquet commerce introuvable');
    }

    public function test_a_big_package_on_a_partly_configured_company(): void
    {
        $existing = $this->company(['item_codes' => ['R01', 'R02', 'R03', 'R04', 'R05', 'R06', 'R07', 'R08']]);
        $plan = PackagePlanner::plan($this->big(), $existing);

        $this->assertSame([], PackageSchema::validate($this->big()));
        $this->assertCount(6, $plan['items']['create']);
        $this->assertCount(8, $plan['items']['skip']);
        $this->assertCount(2, $plan['profiles']['create']);
        $this->assertCount(1, $plan['grids']['create']);
        $this->assertSame(6, count($plan['grids']['create'][0]['grades']));
        $this->assertSame('create', $plan['template']['action']);
        $this->assertTrue($plan['template']['make_default']);
        $this->assertSame('fill', $plan['settings']['action']);
        $this->assertSame(['created' => 11, 'skipped' => 8], $plan['totals']);
    }

    public function test_a_second_application_creates_nothing(): void
    {
        $existing = $this->company(['item_codes' => ['R01', 'R02', 'R03', 'R04', 'R05', 'R06', 'R07', 'R08']]);
        $first = PackagePlanner::plan($this->big(), $existing);
        $after = PackagePlanner::afterApply($existing, $first);
        $second = PackagePlanner::plan($this->big(), $after);

        $this->assertSame(['created' => 0, 'skipped' => 19], $second['totals']);
        $this->assertSame([], $second['items']['create']);
        $this->assertSame('skip', $second['template']['action']);
        $this->assertSame('skip', $second['settings']['action']);
    }

    public function test_matching_ignores_case_and_surrounding_spaces(): void
    {
        $existing = $this->company(['item_codes' => ['r01'], 'profile_names' => ['  employés '], 'grid_names' => ['GRILLE TYPE'], 'template_names' => ['standard']]);
        $plan = PackagePlanner::plan($this->big(), $existing);

        $this->assertCount(13, $plan['items']['create']);
        $this->assertSame([['code' => 'R01', 'label' => 'Rubrique 1']], $plan['items']['skip']);
        $this->assertSame([['name' => 'Employés']], $plan['profiles']['skip']);
        $this->assertCount(1, $plan['profiles']['create']);
        $this->assertSame([], $plan['grids']['create']);
        $this->assertSame('skip', $plan['template']['action']);
    }

    public function test_the_bulletin_template_never_steals_the_default(): void
    {
        $plan = PackagePlanner::plan($this->big(), $this->company(['has_default_template' => true]));

        $this->assertSame('create', $plan['template']['action']);
        $this->assertFalse($plan['template']['make_default']);
    }

    public function test_a_filled_convention_name_is_never_overwritten(): void
    {
        $plan = PackagePlanner::plan($this->big(), $this->company(['convention_name' => 'Notre convention']));
        $this->assertSame('skip', $plan['settings']['action']);

        $blank = PackagePlanner::plan($this->big(), $this->company(['convention_name' => '   ']));
        $this->assertSame('fill', $blank['settings']['action']);
    }

    public function test_item_defaults_come_from_the_package(): void
    {
        $plan = PackagePlanner::plan(['items' => [['code' => 'a1', 'label' => ' Prime ', 'type' => 'gain', 'calc_type' => 'fixed']]], $this->company());
        $row = $plan['items']['create'][0];

        $this->assertSame('A1', $row['code']);
        $this->assertSame('Prime', $row['label']);
        $this->assertTrue($row['subject_to_social']);
        $this->assertTrue($row['taxable']);
        $this->assertFalse($row['is_compulsory']);
        $this->assertNull($row['default_amount']);
        $this->assertSame(200, $row['sort']);
    }

    public function test_starter_packages_are_valid_and_contain_no_invented_values(): void
    {
        $all = PackageStarterData::all();
        $codes = array_column($all, 'code');

        $this->assertCount(8, $all);
        $this->assertSame($codes, array_values(array_unique($codes)));

        foreach ($all as $p) {
            $this->assertSame([], PackageSchema::validate($p['payload']), 'Paquet ' . $p['code']);

            $json = json_encode($p['payload']);

            foreach (['default_amount', 'default_rate', '"amount"', '"rate"', '"grids"'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $json, $p['code'] . ' contient ' . $forbidden);
            }
        }

        $shells = array_filter($all, fn ($p) => PackageSchema::isEmpty($p['payload']));
        $this->assertCount(6, $shells);
        $this->assertSame(['banques', 'btp', 'hotellerie', 'mines', 'enseignement_prive', 'sante_communautaire'], array_values(array_column($shells, 'code')));
    }

    public function test_commerce_on_a_new_company_then_again(): void
    {
        $existing = $this->company();
        $first = PackagePlanner::plan($this->commerce(), $existing);

        $this->assertSame(['created' => 5, 'skipped' => 0], $first['totals']);
        $this->assertTrue($first['template']['make_default']);

        $second = PackagePlanner::plan($this->commerce(), PackagePlanner::afterApply($existing, $first));
        $this->assertSame(['created' => 0, 'skipped' => 5], $second['totals']);
    }

    public function test_commerce_on_a_company_that_already_loaded_the_starter_items(): void
    {
        $existing = $this->company(['item_codes' => ['100200', '100300', '100400']]);
        $plan = PackagePlanner::plan($this->commerce(), $existing);

        $this->assertSame(['created' => 3, 'skipped' => 2], $plan['totals']);
        $this->assertSame(['100200', '100300'], array_column($plan['items']['skip'], 'code'));
        $this->assertCount(1, $plan['profiles']['create']);
        $this->assertSame(['100200', '100300'], array_column($plan['profiles']['create'][0]['lines'], 'code'));
    }

    public function test_summary_lists_what_was_created_and_skipped(): void
    {
        $plan = PackagePlanner::plan($this->commerce(), $this->company(['item_codes' => ['100200']]));
        $s = PackagePlanner::summary($plan);

        $this->assertSame(['100300'], $s['items_created']);
        $this->assertSame(['100200'], $s['items_skipped']);
        $this->assertSame(['Personnel commerce'], $s['profiles_created']);
        $this->assertSame('create', $s['template']);
        $this->assertTrue($s['template_is_default']);
        $this->assertSame('fill', $s['settings']);
        $this->assertSame(['created' => 4, 'skipped' => 1], $s['totals']);
    }

    public function test_planning_does_not_change_its_inputs(): void
    {
        $existing = $this->company(['item_codes' => ['R01']]);
        $copy = $existing;
        PackagePlanner::plan($this->big(), $existing);

        $this->assertSame($copy, $existing);
    }
}
