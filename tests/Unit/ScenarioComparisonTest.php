<?php

namespace Tests\Unit;

use App\Services\Payroll\ScenarioComparison;
use PHPUnit\Framework\TestCase;

class ScenarioComparisonTest extends TestCase
{
    private function summary(int $gross, int $net, int $cost, int $supplement = 0): array
    {
        return ['base' => $gross - 50000, 'gross' => $gross, 'social' => 20000, 'its' => 10000, 'net' => $net, 'supplement' => $supplement, 'employer_cost' => $cost, 'annual_cost' => $cost * 12];
    }

    private function row(array $table, string $key): array
    {
        return array_values(array_filter($table['rows'], fn ($r) => $r['key'] === $key))[0];
    }

    public function test_rows_follow_the_declared_order(): void
    {
        $t = ScenarioComparison::table([['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null]]);

        $this->assertSame(array_keys(ScenarioComparison::ROWS), array_column($t['rows'], 'key'));
        $this->assertSame(0, $t['baseline']);
    }

    /** Le premier scénario calculé sert de base : sa colonne n'a pas d'écart, les autres se comparent à lui. */
    public function test_other_scenarios_are_compared_to_the_first(): void
    {
        $t = ScenarioComparison::table([
            ['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null],
            ['label' => 'B', 'summary' => $this->summary(330000, 275000, 396000), 'error' => null],
        ]);

        $gross = $this->row($t, 'gross');

        $this->assertSame(['value' => '300 000', 'delta' => '', 'direction' => 'base'], $gross['cells'][0]);
        $this->assertSame('330 000', $gross['cells'][1]['value']);
        $this->assertSame('▲ +30 000 (+10,0 %)', $gross['cells'][1]['delta']);
        $this->assertSame('up', $gross['cells'][1]['direction']);
        $this->assertSame('▲ +25 000 (+10,0 %)', $this->row($t, 'net')['cells'][1]['delta']);
        $this->assertSame('4 752 000', $this->row($t, 'annual_cost')['cells'][1]['value']);
    }

    public function test_lower_value_shows_a_decrease_and_equal_value_shows_equal(): void
    {
        $t = ScenarioComparison::table([
            ['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null],
            ['label' => 'B', 'summary' => $this->summary(270000, 225000, 324000), 'error' => null],
        ]);

        $this->assertSame('▼ −30 000 (−10,0 %)', $this->row($t, 'gross')['cells'][1]['delta']);
        $this->assertSame('=', $this->row($t, 'social')['cells'][1]['delta']);
    }

    /** Base nulle (sursalaire de A à 0) : l'écart en FCFA reste affiché, sans pourcentage. */
    public function test_zero_baseline_has_no_percentage(): void
    {
        $t = ScenarioComparison::table([
            ['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000, 0), 'error' => null],
            ['label' => 'B', 'summary' => $this->summary(300000, 250000, 360000, 15000), 'error' => null],
        ]);

        $this->assertSame('▲ +15 000', $this->row($t, 'supplement')['cells'][1]['delta']);
    }

    /** Un scénario non calculé a des cellules vides ; la base passe au premier scénario calculé. */
    public function test_baseline_skips_uncomputed_scenarios(): void
    {
        $t = ScenarioComparison::table([
            ['label' => 'A', 'summary' => null, 'error' => 'Indiquez un salaire de base.'],
            ['label' => 'B', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null],
            ['label' => 'C', 'summary' => $this->summary(330000, 275000, 396000), 'error' => null],
        ]);

        $this->assertSame(1, $t['baseline']);
        $cells = $this->row($t, 'gross')['cells'];
        $this->assertNull($cells[0]['value']);
        $this->assertSame('base', $cells[1]['direction']);
        $this->assertSame('▲ +30 000 (+10,0 %)', $cells[2]['delta']);
    }

    public function test_no_computed_scenario(): void
    {
        $t = ScenarioComparison::table([['label' => 'A', 'summary' => null, 'error' => 'x']]);

        $this->assertNull($t['baseline']);
        $this->assertNull($this->row($t, 'net')['cells'][0]['value']);
    }

    public function test_base_salary_is_the_first_row_and_is_compared(): void
    {
        $t = ScenarioComparison::table([
            ['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null],
            ['label' => 'B', 'summary' => $this->summary(330000, 275000, 396000), 'error' => null],
        ]);

        $this->assertSame('base', $t['rows'][0]['key']);
        $this->assertSame('250 000', $t['rows'][0]['cells'][0]['value']);
        $this->assertSame('280 000', $t['rows'][0]['cells'][1]['value']);
        $this->assertSame('▲ +30 000 (+12,0 %)', $t['rows'][0]['cells'][1]['delta']);
    }

    private function solved(string $kind, int $target, int $base, int $gap, bool $reached = true): array
    {
        return ['label' => 'A', 'error' => null, 'summary' => $this->summary($base + 50000, 250000, 360000) + ['solve' => ['kind' => $kind, 'target' => $target, 'reached' => $reached, 'gap' => $gap]]];
    }

    public function test_notes_for_a_target_reached_exactly(): void
    {
        $n = ScenarioComparison::solveNotes([$this->solved('net', 300000, 350000, 0)]);

        $this->assertSame('ok', $n[0]['level']);
        $this->assertSame('A', $n[0]['label']);
        $this->assertSame('Net voulu (300 000 FCFA) atteint exactement. Salaire de base nécessaire : 350 000 FCFA.', $n[0]['text']);
    }

    public function test_notes_for_a_target_slightly_exceeded(): void
    {
        $n = ScenarioComparison::solveNotes([$this->solved('gross', 400000, 351000, 500)]);

        $this->assertSame('ok', $n[0]['level']);
        $this->assertSame("Brut voulu (400 000 FCFA) atteint, avec 500 FCFA de plus (arrondis, ou tranches de l'impôt). Salaire de base nécessaire : 351 000 FCFA.", $n[0]['text']);
    }

    public function test_notes_when_fixed_items_exceed_the_target(): void
    {
        $n = ScenarioComparison::solveNotes([$this->solved('gross', 80000, 0, 20000)]);

        $this->assertSame('warning', $n[0]['level']);
        $this->assertStringStartsWith('Même avec un salaire de base nul, le brut dépasse le brut voulu (80 000 FCFA) de 20 000 FCFA', $n[0]['text']);
    }

    public function test_notes_when_the_target_cannot_be_reached(): void
    {
        $n = ScenarioComparison::solveNotes([$this->solved('net', 900000, 6000000, -100000, false)]);

        $this->assertSame('warning', $n[0]['level']);
        $this->assertStringContainsString('ne peut pas être atteint', $n[0]['text']);
    }

    public function test_no_note_for_a_scenario_starting_from_the_base(): void
    {
        $this->assertSame([], ScenarioComparison::solveNotes([['label' => 'A', 'summary' => $this->summary(300000, 250000, 360000), 'error' => null], ['label' => 'B', 'summary' => null, 'error' => 'x']]));
    }
}
