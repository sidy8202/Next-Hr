<?php

namespace Tests\Unit;

use App\Services\Payroll\PackageSchema;
use PHPUnit\Framework\TestCase;

class PackageSchemaTest extends TestCase
{
    protected function item(array $o = []): array
    {
        return $o + ['code' => '100200', 'label' => 'Indemnité spéciale', 'type' => 'gain', 'calc_type' => 'fixed'];
    }

    protected function has(array $errors, string $needle): bool
    {
        foreach ($errors as $e) {
            if (str_contains($e, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function test_a_minimal_package_is_valid(): void
    {
        $payload = [
            'items' => [$this->item(), $this->item(['code' => '100300', 'label' => "Prime d'ancienneté", 'calc_type' => 'scale', 'scale' => [['from_years' => 2, 'rate' => 3]]])],
            'profiles' => [['name' => 'Personnel', 'items' => [['code' => '100200', 'amount' => 2000], ['code' => '100300']]]],
            'grids' => [['name' => 'Grille', 'grades' => [['code' => 'E8B', 'steps' => [['label' => 'Échelon 1', 'amount' => 100000]]]]]],
            'template' => ['name' => 'Standard', 'show_item_codes' => true],
            'settings' => ['convention_name' => 'Convention X'],
        ];

        $this->assertSame([], PackageSchema::validate($payload));
        $this->assertSame([], PackageSchema::validate([]));
    }

    public function test_a_payload_that_is_not_an_object_is_refused(): void
    {
        $this->assertSame(['Le contenu du paquet doit être un objet JSON.'], PackageSchema::validate('texte'));
        $this->assertSame(['Le contenu du paquet doit être un objet JSON.'], PackageSchema::validate(null));
    }

    public function test_unknown_top_level_key_is_refused(): void
    {
        $this->assertTrue($this->has(PackageSchema::validate(['rules' => []]), 'Clé inconnue « rules »'));
    }

    public function test_engine_items_are_refused(): void
    {
        $e = PackageSchema::validate(['items' => [$this->item(['calc_type' => 'system']), $this->item(['code' => 'X1', 'is_system' => true])]]);

        $this->assertTrue($this->has($e, 'items[0].calc_type'));
        $this->assertTrue($this->has($e, 'items[1] : les rubriques fournies par le moteur'));
    }

    public function test_duplicate_item_codes_are_refused_whatever_the_case(): void
    {
        $e = PackageSchema::validate(['items' => [$this->item(['code' => 'ab1']), $this->item(['code' => 'AB1'])]]);

        $this->assertTrue($this->has($e, 'items[1].code : le code « AB1 » est en double'));
    }

    public function test_item_fields_are_checked(): void
    {
        $e = PackageSchema::validate(['items' => [$this->item(['code' => 'TROP-LONG-CODE', 'label' => '', 'type' => 'bonus', 'default_amount' => -5, 'default_rate' => 120, 'taxable' => 'oui'])]]);

        $this->assertTrue($this->has($e, 'items[0].code'));
        $this->assertTrue($this->has($e, 'items[0].label'));
        $this->assertTrue($this->has($e, 'items[0].type'));
        $this->assertTrue($this->has($e, 'items[0].default_amount'));
        $this->assertTrue($this->has($e, 'items[0].default_rate'));
        $this->assertTrue($this->has($e, 'items[0].taxable'));
    }

    public function test_percent_item_needs_a_reference_inside_the_package(): void
    {
        $ok = PackageSchema::validate(['items' => [$this->item(), $this->item(['code' => 'P1', 'calc_type' => 'percent_item', 'ref_code' => '100200', 'default_rate' => 10])]]);
        $this->assertSame([], $ok);

        $missing = PackageSchema::validate(['items' => [$this->item(['code' => 'P1', 'calc_type' => 'percent_item', 'ref_code' => 'ZZZ'])]]);
        $this->assertTrue($this->has($missing, 'items[0].ref_code : la rubrique de référence doit exister'));

        $self = PackageSchema::validate(['items' => [$this->item(['code' => 'P1', 'calc_type' => 'percent_item', 'ref_code' => 'p1'])]]);
        $this->assertTrue($this->has($self, 'ne peut pas se référencer elle-même'));

        $stray = PackageSchema::validate(['items' => [$this->item(['ref_code' => '100200'])]]);
        $this->assertTrue($this->has($stray, "n'est permis que pour le type de calcul « percent_item »"));
    }

    public function test_scale_is_only_for_scale_items_and_must_be_clean(): void
    {
        $stray = PackageSchema::validate(['items' => [$this->item(['scale' => [['from_years' => 1, 'rate' => 2]]])]]);
        $this->assertTrue($this->has($stray, "n'est permis que pour le type de calcul « scale »"));

        $dup = PackageSchema::validate(['items' => [$this->item(['calc_type' => 'scale', 'scale' => [['from_years' => 2, 'rate' => 3], ['from_years' => 2, 'rate' => 4]]])]]);
        $this->assertTrue($this->has($dup, '« à partir de 2 ans » est en double'));

        $bad = PackageSchema::validate(['items' => [$this->item(['calc_type' => 'scale', 'scale' => [['from_years' => 'x', 'rate' => 3]]])]]);
        $this->assertTrue($this->has($bad, 'items[0].scale[0]'));
    }

    public function test_profiles_can_only_use_items_of_the_package(): void
    {
        $e = PackageSchema::validate([
            'items' => [$this->item()],
            'profiles' => [
                ['name' => 'A', 'items' => [['code' => 'INCONNU']]],
                ['name' => 'a', 'items' => [['code' => '100200'], ['code' => '100200']]],
            ],
        ]);

        $this->assertTrue($this->has($e, 'profiles[0].items[0].code : la rubrique doit exister dans le paquet'));
        $this->assertTrue($this->has($e, 'profiles[1].name : le nom « a » est en double'));
        $this->assertTrue($this->has($e, 'profiles[1].items[1].code : la rubrique « 100200 » est déjà dans ce modèle'));
    }

    public function test_grid_steps_need_an_amount(): void
    {
        $e = PackageSchema::validate(['grids' => [['name' => 'G', 'grades' => [['code' => 'E', 'steps' => [['label' => 'Échelon 1']]]]]]]);

        $this->assertTrue($this->has($e, 'grids[0].grades[0].steps[0]'));
    }

    public function test_template_and_settings_are_strict(): void
    {
        $t = PackageSchema::validate(['template' => ['name' => 'S', 'group_lines' => 'oui']]);
        $this->assertTrue($this->has($t, 'template.group_lines'));

        $s = PackageSchema::validate(['settings' => ['days_basis' => 26]]);
        $this->assertTrue($this->has($s, 'settings.days_basis : seule la clé « convention_name » est permise'));
    }

    public function test_counts_empty_and_required_resources(): void
    {
        $payload = ['items' => [$this->item()], 'profiles' => [['name' => 'P', 'items' => []]], 'template' => ['name' => 'S'], 'settings' => ['convention_name' => 'X']];

        $this->assertSame(['items' => 1, 'profiles' => 1, 'grids' => 0, 'grades' => 0, 'template' => 1, 'settings' => 1], PackageSchema::counts($payload));
        $this->assertFalse(PackageSchema::isEmpty($payload));
        $this->assertSame(['pay_items', 'pay_profiles', 'payroll_settings'], PackageSchema::requiredResources($payload));

        $this->assertTrue(PackageSchema::isEmpty([]));
        $this->assertTrue(PackageSchema::isEmpty(['items' => [], 'template' => []]));
        $this->assertSame([], PackageSchema::requiredResources([]));
    }
}
