<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollDashboardStats;
use PHPUnit\Framework\TestCase;

class PayrollDashboardStatsTest extends TestCase
{
    private function sums(array $over = []): array
    {
        // employee_contributions = total salarié du moteur, ITS compris (500 000 dont 310 000 d'ITS).
        return $over + ['count' => 12, 'gross' => 3300000, 'net_to_pay' => 2640000, 'employee_contributions' => 500000, 'its' => 310000, 'employer_total' => 560000, 'employer_cost' => 3860000];
    }

    /** Le total salarié du moteur contient déjà l'ITS : il ne faut pas l'ajouter une seconde fois (régression du lot 17a). */
    public function test_deductions_do_not_count_the_tax_twice(): void
    {
        $t = PayrollDashboardStats::totals($this->sums());

        $this->assertSame(12, $t['count']);
        $this->assertSame(500000, $t['deductions']);
        $this->assertSame(160000, $t['gross'] - $t['deductions'] - $t['net_to_pay']);
        $this->assertSame(2640000, $t['net_to_pay']);
        $this->assertSame(3860000, $t['employer_cost']);
    }

    public function test_totals_tolerate_missing_keys(): void
    {
        $t = PayrollDashboardStats::totals([]);

        $this->assertSame(['count' => 0, 'gross' => 0, 'net_to_pay' => 0, 'deductions' => 0, 'employer_total' => 0, 'employer_cost' => 0], $t);
    }

    /** 2 640 000 contre 2 500 000 : +140 000, soit +5,6 %. */
    public function test_delta_rises_by_five_point_six_percent(): void
    {
        $d = PayrollDashboardStats::delta(2640000, 2500000);

        $this->assertSame(140000, $d['diff']);
        $this->assertSame(5.6, $d['percent']);
        $this->assertSame('up', $d['direction']);
    }

    public function test_delta_falls(): void
    {
        $d = PayrollDashboardStats::delta(620490, 625098);

        $this->assertSame(-4608, $d['diff']);
        $this->assertSame(-0.7, $d['percent']);
        $this->assertSame('down', $d['direction']);
    }

    /** Arrondi à la demi-unité supérieure : 1 sur 8 = 12,5 % ; 1 sur 16 = 6,25 % → 6,3 %. */
    public function test_percent_rounds_half_up(): void
    {
        $this->assertSame(12.5, PayrollDashboardStats::delta(9, 8)['percent']);
        $this->assertSame(6.3, PayrollDashboardStats::delta(17, 16)['percent']);
        $this->assertSame(-6.3, PayrollDashboardStats::delta(15, 16)['percent']);
    }

    public function test_delta_without_previous_month_is_null(): void
    {
        $this->assertNull(PayrollDashboardStats::delta(100, null));
    }

    public function test_delta_from_zero_has_no_percent(): void
    {
        $d = PayrollDashboardStats::delta(500, 0);

        $this->assertSame(500, $d['diff']);
        $this->assertNull($d['percent']);
        $this->assertSame('up', $d['direction']);
    }

    public function test_delta_flat(): void
    {
        $d = PayrollDashboardStats::delta(1000, 1000);

        $this->assertSame(0, $d['diff']);
        $this->assertSame(0.0, $d['percent']);
        $this->assertSame('flat', $d['direction']);
    }

    public function test_compare_covers_every_metric(): void
    {
        $cur = PayrollDashboardStats::totals($this->sums());
        $prev = PayrollDashboardStats::totals($this->sums(['net_to_pay' => 2500000]));
        $c = PayrollDashboardStats::compare($cur, $prev);

        $this->assertSame(array_keys(PayrollDashboardStats::METRICS), array_keys($c));
        $this->assertSame(140000, $c['net_to_pay']['diff']);
        $this->assertSame('flat', $c['gross']['direction']);
        $this->assertSame([null, null, null, null, null], array_values(PayrollDashboardStats::compare($cur, null)));
    }

    public function test_texts(): void
    {
        $this->assertSame('2 640 000', PayrollDashboardStats::number(2640000));
        $this->assertSame('+140 000', PayrollDashboardStats::signed(140000));
        $this->assertSame('−4 608', PayrollDashboardStats::signed(-4608));
        $this->assertSame('0', PayrollDashboardStats::signed(0));
        $this->assertSame('▲ +140 000 FCFA (+5,6 %) par rapport au mois précédent.', PayrollDashboardStats::deltaText(PayrollDashboardStats::delta(2640000, 2500000)));
        $this->assertSame('▼ −4 608 FCFA (−0,7 %) par rapport au mois précédent.', PayrollDashboardStats::deltaText(PayrollDashboardStats::delta(620490, 625098)));
        $this->assertSame('Identique au mois précédent.', PayrollDashboardStats::deltaText(PayrollDashboardStats::delta(5, 5)));
        $this->assertSame('▲ +500 FCFA par rapport au mois précédent.', PayrollDashboardStats::deltaText(PayrollDashboardStats::delta(500, 0)));
        $this->assertStringContainsString('Pas de comparaison', PayrollDashboardStats::deltaText(null));
    }

    public function test_short_delta(): void
    {
        $this->assertSame('▲ +140 000 (+5,6 %)', PayrollDashboardStats::deltaShort(PayrollDashboardStats::delta(2640000, 2500000)));
        $this->assertSame('▼ −4 608 (−0,7 %)', PayrollDashboardStats::deltaShort(PayrollDashboardStats::delta(620490, 625098)));
        $this->assertSame('▲ +500', PayrollDashboardStats::deltaShort(PayrollDashboardStats::delta(500, 0)));
        $this->assertSame('=', PayrollDashboardStats::deltaShort(PayrollDashboardStats::delta(5, 5)));
        $this->assertSame('—', PayrollDashboardStats::deltaShort(null));
    }
}
