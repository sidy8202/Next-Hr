<?php

namespace Tests\Unit;

use App\Services\Reports\GridGapReport;
use App\Services\Reports\ReportThresholds;
use PHPUnit\Framework\TestCase;

class GridGapReportTest extends TestCase
{
    private const GRADE = ['min' => 100000, 'max' => 300000];

    public function test_classification(): void
    {
        $this->assertSame('below', GridGapReport::classify(['base' => 90000, 'step_amount' => null], self::GRADE));
        $this->assertSame('above', GridGapReport::classify(['base' => 310000, 'step_amount' => null], self::GRADE));
        $this->assertSame('ok', GridGapReport::classify(['base' => 200000, 'step_amount' => 200000], self::GRADE));
        $this->assertSame('ok', GridGapReport::classify(['base' => 200000, 'step_amount' => null], self::GRADE));
        $this->assertSame('step_mismatch', GridGapReport::classify(['base' => 200000, 'step_amount' => 180000], self::GRADE));
        $this->assertSame('no_grade', GridGapReport::classify(['base' => 200000, 'step_amount' => null], null));
    }

    /** Les bornes de la catégorie sont comprises : le minimum et le maximum eux-mêmes sont conformes. */
    public function test_boundaries_are_compliant(): void
    {
        $this->assertSame('ok', GridGapReport::classify(['base' => 100000, 'step_amount' => 100000], self::GRADE));
        $this->assertSame('ok', GridGapReport::classify(['base' => 300000, 'step_amount' => 300000], self::GRADE));
        $this->assertSame('below', GridGapReport::classify(['base' => 99999, 'step_amount' => null], self::GRADE));
        $this->assertSame('above', GridGapReport::classify(['base' => 300001, 'step_amount' => null], self::GRADE));
    }

    private function row(string $dept, string $cat, string $status): array
    {
        return ['department' => $dept, 'category' => $cat, 'status' => $status];
    }

    /** @return array<int, array{department: string, category: string, status: string}> */
    private function rows(): array
    {
        $rows = [];

        foreach (['below', 'ok', 'ok', 'ok', 'ok'] as $st) {
            $rows[] = $this->row('Finance', 'A1', $st);
        }

        foreach (['above', 'ok', 'ok', 'ok'] as $st) {
            $rows[] = $this->row('Logistique', 'A1', $st);
        }

        $rows[] = $this->row('Direction', 'B1', 'below');
        $rows[] = $this->row('Audit', 'B1', 'ok');
        $rows[] = $this->row('Juridique', 'B1', 'ok');
        $rows[] = ['department' => 'Finance', 'category' => null, 'status' => 'no_grade'];
        $rows[] = ['department' => 'Audit', 'category' => null, 'status' => 'no_grade'];

        return $rows;
    }

    public function test_summary_counts_and_groups(): void
    {
        $s = GridGapReport::summarize($this->rows(), 1);

        $this->assertFalse($s['too_small']);
        $this->assertSame(12, $s['compared']);
        $this->assertSame(2, $s['below']);
        $this->assertSame(1, $s['above']);
        $this->assertSame(0, $s['step_mismatch']);
        $this->assertSame(9, $s['ok']);
        $this->assertSame(2, $s['no_grade']);
        $this->assertSame(1, $s['no_salary']);

        // Direction, Audit et Juridique (1 personne chacun) atteignent ensemble 3 : « Autres services ».
        $names = array_column($s['departments']['items'], 'name');
        $this->assertSame(['Finance', 'Logistique', 'Autres services'], $names);
        $this->assertSame(1, $s['departments']['items'][0]['below']);
        $this->assertSame(3, $s['departments']['items'][2]['count']);
        $this->assertSame(1, $s['departments']['items'][2]['below']);
        $this->assertSame(0, $s['departments']['hidden_people']);

        $this->assertSame(['A1', 'B1'], array_column($s['categories']['items'], 'name'));
        $this->assertSame(9, $s['categories']['items'][0]['count']);
    }

    public function test_too_few_people_hides_everything(): void
    {
        $s = GridGapReport::summarize([$this->row('Finance', 'A1', 'below'), $this->row('Finance', 'A1', 'ok')], 0);

        $this->assertTrue($s['too_small']);
        $this->assertSame([], $s['departments']['items']);
        $this->assertSame([], $s['categories']['items']);
    }

    public function test_small_groups_that_stay_small_are_hidden(): void
    {
        $m = GridGapReport::mask([
            'Finance' => ['count' => 5, 'below' => 1, 'above' => 0, 'step_mismatch' => 0],
            'Direction' => ['count' => 1, 'below' => 1, 'above' => 0, 'step_mismatch' => 0],
            'Audit' => ['count' => 1, 'below' => 0, 'above' => 0, 'step_mismatch' => 0],
        ], 'Autres services');

        $this->assertSame(['Finance'], array_column($m['items'], 'name'));
        $this->assertSame(2, $m['hidden_people']);
        $this->assertSame(2, $m['hidden_groups']);
    }

    public function test_unnamed_department_and_category_have_a_label(): void
    {
        $rows = [];

        foreach (range(1, 3) as $_) {
            $rows[] = ['department' => null, 'category' => null, 'status' => 'ok'];
        }

        $s = GridGapReport::summarize($rows, 0);

        $this->assertSame('Sans service', $s['departments']['items'][0]['name']);
        $this->assertSame('Sans catégorie', $s['categories']['items'][0]['name']);
    }

    public function test_sentences_for_a_worrying_situation(): void
    {
        $s = GridGapReport::sentences(GridGapReport::summarize($this->rows(), 1), ReportThresholds::defaults());

        $this->assertSame('2 employés sont payés sous le minimum de leur catégorie, soit 17 % des 12 comparés. Cette part dépasse le seuil de 10 % fixé pour ce rapport.', $s[0]['text']);
        $this->assertSame('1 employé est payé au-dessus du maximum de sa catégorie.', $s[1]['text']);
        $this->assertSame('2 employés occupent un poste sans catégorie salariale : ils ne sont pas comparés.', $s[2]['text']);
        $this->assertSame('1 employé en poste n\'a aucun salaire en vigueur à cette date.', $s[3]['text']);
        $this->assertCount(4, $s);
    }

    public function test_a_single_employee_below_the_minimum_stays_under_the_threshold(): void
    {
        $rows = [$this->row('A', 'A1', 'below')];

        foreach (range(1, 11) as $_) {
            $rows[] = $this->row('A', 'A1', 'ok');
        }

        $s = GridGapReport::sentences(GridGapReport::summarize($rows, 0), ReportThresholds::defaults());

        $this->assertSame('1 employé est payé sous le minimum de sa catégorie, soit 8 % des 12 comparés.', $s[0]['text']);
        $this->assertStringContainsString('à partir de 10 %', $s[0]['rule']);
    }

    public function test_no_gap_and_step_mismatch_sentences(): void
    {
        $ok = [];

        foreach (range(1, 4) as $_) {
            $ok[] = $this->row('A', 'A1', 'ok');
        }

        $none = GridGapReport::sentences(GridGapReport::summarize($ok, 0), ReportThresholds::defaults());
        $this->assertSame('Aucun employé n\'est payé sous le minimum de sa catégorie.', $none[0]['text']);
        $this->assertCount(1, $none);

        $mismatch = array_merge($ok, [$this->row('A', 'A1', 'step_mismatch'), $this->row('A', 'A1', 'step_mismatch')]);
        $s = GridGapReport::sentences(GridGapReport::summarize($mismatch, 0), ReportThresholds::defaults());
        $this->assertSame('2 employés ont un salaire de base différent du montant de leur échelon, tout en restant dans la fourchette de la catégorie.', $s[1]['text']);
    }

    public function test_too_small_sentence_protects_individual_salaries(): void
    {
        $s = GridGapReport::sentences(GridGapReport::summarize([$this->row('A', 'A1', 'below')], 0), ReportThresholds::defaults());

        $this->assertCount(1, $s);
        $this->assertSame('Moins de 3 personnes sont comparées : les chiffres ne sont pas affichés, pour ne pas révéler un salaire individuel.', $s[0]['text']);
    }

    public function test_summary_never_carries_personal_data(): void
    {
        $s = GridGapReport::summarize($this->rows(), 1);

        foreach (array_merge($s['departments']['items'], $s['categories']['items']) as $item) {
            $this->assertSame(['name', 'count', 'below', 'above', 'step_mismatch'], array_keys($item));
        }
    }
}
