<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollFingerprint;
use PHPUnit\Framework\TestCase;

class PayrollFingerprintTest extends TestCase
{
    private function input(): array
    {
        return ['salary' => ['base' => 55000, 'supplement' => 211919, 'mode' => 'gross'], 'items' => [['code' => '100600', 'amount' => 20000]], 'factor' => 1.0];
    }

    public function test_the_same_data_gives_the_same_fingerprint(): void
    {
        $this->assertSame(PayrollFingerprint::hash($this->input()), PayrollFingerprint::hash($this->input()));
        $this->assertSame(32, strlen(PayrollFingerprint::hash($this->input())));
    }

    public function test_the_order_of_keys_does_not_matter(): void
    {
        $a = ['salary' => ['base' => 1, 'supplement' => 2], 'factor' => 1.0];
        $b = ['factor' => 1.0, 'salary' => ['supplement' => 2, 'base' => 1]];

        $this->assertSame(PayrollFingerprint::hash($a), PayrollFingerprint::hash($b));
    }

    public function test_any_changed_amount_changes_the_fingerprint(): void
    {
        $changed = $this->input();
        $changed['salary']['base'] = 75000;                               // une augmentation entre le calcul et la clôture

        $this->assertNotSame(PayrollFingerprint::hash($this->input()), PayrollFingerprint::hash($changed));
    }

    public function test_the_order_of_a_list_matters(): void
    {
        $a = ['items' => [['code' => 'A'], ['code' => 'B']]];
        $b = ['items' => [['code' => 'B'], ['code' => 'A']]];

        $this->assertNotSame(PayrollFingerprint::hash($a), PayrollFingerprint::hash($b));
    }
}
