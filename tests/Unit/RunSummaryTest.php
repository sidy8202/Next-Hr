<?php

namespace Tests\Unit;

use App\Services\Payroll\RunSummary;
use PHPUnit\Framework\TestCase;

class RunSummaryTest extends TestCase
{
    private function rows(): array
    {
        return [
            ['department' => 'Commerce', 'net_known' => 225000, 'net_to_pay' => 224955, 'status' => 'ok'],           // A
            ['department' => 'Transit', 'net_known' => 200049, 'net_to_pay' => 182644, 'status' => 'variables'],     // B avec absence
            ['department' => 'Transit', 'net_known' => 200049, 'net_to_pay' => 212891, 'status' => 'to_check'],      // C
        ];
    }

    /** Totaux du plan : 625 098 de net connu, 620 490 de net calculé, soit −4 608. */
    public function test_totals_compare_known_and_calculated_nets(): void
    {
        $s = RunSummary::summarize($this->rows());

        $this->assertSame(3, $s['count']);
        $this->assertSame(625098, $s['compared']['net_known']);
        $this->assertSame(620490, $s['compared']['net_calculated']);
        $this->assertSame(-4608, $s['compared']['gap']);
        $this->assertSame(620490, $s['net_total']);
        $this->assertSame(['ok' => 1, 'variables' => 1, 'to_check' => 1, 'no_reference' => 0], $s['statuses']);
    }

    public function test_alerts_are_grouped_by_department(): void
    {
        $d = RunSummary::summarize($this->rows())['departments'];

        $this->assertSame(['Commerce', 'Transit'], array_keys($d));
        $this->assertSame(1, $d['Commerce']['ok']);
        $this->assertSame(2, $d['Transit']['count']);
        $this->assertSame(1, $d['Transit']['to_check']);
        $this->assertSame(1, $d['Transit']['variables']);
        $this->assertSame(400098, $d['Transit']['net_known']);
    }

    public function test_employees_without_a_known_net_are_left_out_of_the_comparison(): void
    {
        $rows = $this->rows();
        $rows[] = ['department' => 'Commerce', 'net_known' => null, 'net_to_pay' => 300000, 'status' => 'no_reference'];

        $s = RunSummary::summarize($rows);

        $this->assertSame(3, $s['compared']['count']);
        $this->assertSame(-4608, $s['compared']['gap']);                  // inchangé
        $this->assertSame(920490, $s['net_total']);                       // mais le net total à payer les compte
        $this->assertSame(1, $s['statuses']['no_reference']);
        $this->assertSame(1, $s['departments']['Commerce']['no_reference']);
    }

    public function test_employees_without_a_department_come_last(): void
    {
        $rows = $this->rows();
        array_unshift($rows, ['department' => null, 'net_known' => 100000, 'net_to_pay' => 100000, 'status' => 'ok']);

        $keys = array_keys(RunSummary::summarize($rows)['departments']);

        $this->assertSame(['Commerce', 'Transit', RunSummary::NO_DEPARTMENT], $keys);
    }

    public function test_an_empty_run(): void
    {
        $s = RunSummary::summarize([]);

        $this->assertSame(0, $s['count']);
        $this->assertSame(0, $s['compared']['gap']);
        $this->assertSame([], $s['departments']);
    }
}
