<?php

namespace Tests\Unit;

use App\Services\Payroll\Proration;
use PHPUnit\Framework\TestCase;

/** Politique de l'entreprise pour une arrivée ou un départ en cours de mois. Novembre 2026 : 30 jours. */
class ProrationPolicyTest extends TestCase
{
    private function factor(?string $hire, ?string $exit, array $policy): float
    {
        return Proration::presenceFactor($hire, $exit, '2026-11-01', '2026-11-30', 30, $policy);
    }

    public function test_without_policy_the_days_before_arrival_are_removed(): void
    {
        $this->assertEqualsWithDelta(26 / 30, $this->factor('2026-11-05', null, []), 0.0001);
        $this->assertSame(0.5, $this->factor('2026-11-16', null, []));
    }

    /** Règle courante : mois entier si l'employé arrive au plus tard le 5, sinon on retire les jours avant l'arrivée. */
    public function test_arrival_threshold_pays_the_full_month_up_to_the_limit_day(): void
    {
        $policy = ['arrival' => ['rule' => 'threshold', 'days' => 5]];

        $this->assertSame(1.0, $this->factor('2026-11-02', null, $policy));
        $this->assertSame(1.0, $this->factor('2026-11-05', null, $policy));        // le 5 : mois entier
        $this->assertEqualsWithDelta(25 / 30, $this->factor('2026-11-06', null, $policy), 0.0001);   // le 6 : 5 jours retirés
        $this->assertSame(0.5, $this->factor('2026-11-16', null, $policy));
    }

    public function test_the_limit_day_is_chosen_by_the_company(): void
    {
        $this->assertSame(1.0, $this->factor('2026-11-10', null, ['arrival' => ['rule' => 'threshold', 'days' => 10]]));
        $this->assertEqualsWithDelta(20 / 30, $this->factor('2026-11-11', null, ['arrival' => ['rule' => 'threshold', 'days' => 10]]), 0.0001);
        // une limite à 0 revient à toujours proratiser
        $this->assertEqualsWithDelta(28 / 30, $this->factor('2026-11-02', null, ['arrival' => ['rule' => 'threshold', 'days' => 0]]), 0.0001);
    }

    public function test_arrival_full_always_pays_the_whole_month(): void
    {
        $this->assertSame(1.0, $this->factor('2026-11-20', null, ['arrival' => ['rule' => 'full']]));
        $this->assertSame(1.0, $this->factor('2026-11-30', null, ['arrival' => ['rule' => 'full']]));
    }

    public function test_arrival_prorate_never_waives(): void
    {
        $this->assertEqualsWithDelta(28 / 30, $this->factor('2026-11-02', null, ['arrival' => ['rule' => 'prorate', 'days' => 5]]), 0.0001);
    }

    public function test_departure_threshold_pays_the_full_month_when_leaving_in_the_last_days(): void
    {
        $policy = ['departure' => ['rule' => 'threshold', 'days' => 5]];

        $this->assertSame(1.0, $this->factor('2020-01-01', '2026-11-27', $policy));    // 3 jours restent après le départ
        $this->assertSame(1.0, $this->factor('2020-01-01', '2026-11-26', $policy));    // 4 jours restent
        $this->assertEqualsWithDelta(25 / 30, $this->factor('2020-01-01', '2026-11-25', $policy), 0.0001);  // 5 jours restent : prorata
        $this->assertEqualsWithDelta(10 / 30, $this->factor('2020-01-01', '2026-11-10', $policy), 0.0001);
    }

    public function test_departure_full_and_prorate(): void
    {
        $this->assertSame(1.0, $this->factor('2020-01-01', '2026-11-10', ['departure' => ['rule' => 'full']]));
        $this->assertEqualsWithDelta(10 / 30, $this->factor('2020-01-01', '2026-11-10', ['departure' => ['rule' => 'prorate', 'days' => 5]]), 0.0001);
    }

    public function test_both_waivers_apply_together(): void
    {
        $policy = ['arrival' => ['rule' => 'threshold', 'days' => 5], 'departure' => ['rule' => 'threshold', 'days' => 5]];

        $this->assertSame(1.0, $this->factor('2026-11-04', '2026-11-28', $policy));
    }

    public function test_no_policy_can_pay_someone_who_was_not_there_at_all(): void
    {
        $full = ['arrival' => ['rule' => 'full'], 'departure' => ['rule' => 'full']];

        $this->assertSame(0.0, $this->factor('2026-12-03', null, $full));              // embauché le mois suivant
        $this->assertSame(0.0, $this->factor('2020-01-01', '2026-10-31', $full));      // parti le mois précédent
    }

    public function test_a_31_day_month_with_the_threshold(): void
    {
        $factor = Proration::presenceFactor('2026-12-06', null, '2026-12-01', '2026-12-31', 30, ['arrival' => ['rule' => 'threshold', 'days' => 5]]);

        $this->assertEqualsWithDelta(25 / 30, $factor, 0.0001);                         // 5 jours avant l'arrivée, sur la base de 30
        $this->assertSame(1.0, Proration::presenceFactor('2026-12-05', null, '2026-12-01', '2026-12-31', 30, ['arrival' => ['rule' => 'threshold', 'days' => 5]]));
    }

    public function test_waived_helper(): void
    {
        $this->assertTrue(Proration::waived(['rule' => 'threshold', 'days' => 5], 5, 30, true));
        $this->assertFalse(Proration::waived(['rule' => 'threshold', 'days' => 5], 6, 30, true));
        $this->assertTrue(Proration::waived(['rule' => 'threshold', 'days' => 5], 26, 30, false));
        $this->assertFalse(Proration::waived(['rule' => 'threshold', 'days' => 5], 25, 30, false));
        $this->assertFalse(Proration::waived([], 3, 30, true));
    }
}
