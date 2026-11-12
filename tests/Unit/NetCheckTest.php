<?php

namespace Tests\Unit;

use App\Services\Payroll\NetCheck;
use PHPUnit\Framework\TestCase;

class NetCheckTest extends TestCase
{
    /** Employé A (bulletin 1) : net connu 225 000, net calculé 224 955. L'écart de 45 est un arrondi d'ITS. */
    public function test_a_small_gap_within_tolerance_is_ok(): void
    {
        $r = NetCheck::classify(225000, 224955, 224955, 100);

        $this->assertSame(NetCheck::OK, $r['status']);
        $this->assertSame(-45, $r['structure_gap']);
        $this->assertSame(0, $r['variables_effect']);
    }

    /** Employé B avec 3 jours d'absence : le net du contrat reste conforme, la baisse vient des variables. */
    public function test_a_gap_caused_by_variables_is_explained_not_alerted(): void
    {
        $r = NetCheck::classify(200049, 200096, 182644, 100);

        $this->assertSame(NetCheck::VARIABLES, $r['status']);
        $this->assertSame(47, $r['structure_gap']);
        $this->assertSame(-17452, $r['variables_effect']);
    }

    /** Employé C : la base a augmenté mais le net connu n'a pas été mis à jour. */
    public function test_a_structural_gap_beyond_tolerance_needs_checking(): void
    {
        $r = NetCheck::classify(200049, 212891, 212891, 100);

        $this->assertSame(NetCheck::TO_CHECK, $r['status']);
        $this->assertSame(12842, $r['structure_gap']);
    }

    public function test_a_structural_gap_is_checked_even_when_variables_exist(): void
    {
        $r = NetCheck::classify(200049, 212891, 195000, 100);

        $this->assertSame(NetCheck::TO_CHECK, $r['status']);
        $this->assertSame(-17891, $r['variables_effect']);
    }

    public function test_without_a_known_net_there_is_nothing_to_compare(): void
    {
        $r = NetCheck::classify(null, 200096, 190000, 100);

        $this->assertSame(NetCheck::NO_REFERENCE, $r['status']);
        $this->assertNull($r['structure_gap']);
        $this->assertSame(-10096, $r['variables_effect']);
    }

    public function test_the_tolerance_limit_itself_is_accepted(): void
    {
        $this->assertSame(NetCheck::OK, NetCheck::classify(1000, 1100, 1100, 100)['status']);
        $this->assertSame(NetCheck::OK, NetCheck::classify(1100, 1000, 1000, 100)['status']);
        $this->assertSame(NetCheck::TO_CHECK, NetCheck::classify(1000, 1101, 1101, 100)['status']);
    }

    public function test_a_zero_tolerance_demands_an_exact_match(): void
    {
        $this->assertSame(NetCheck::TO_CHECK, NetCheck::classify(1000, 1001, 1001, 0)['status']);
        $this->assertSame(NetCheck::OK, NetCheck::classify(1000, 1000, 1000, 0)['status']);
    }
}
