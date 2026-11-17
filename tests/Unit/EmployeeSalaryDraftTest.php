<?php

namespace Tests\Unit;

use App\Services\Payroll\EmployeeSalaryDraft;
use PHPUnit\Framework\TestCase;

class EmployeeSalaryDraftTest extends TestCase
{
    private function in(array $over = []): array
    {
        return $over + ['position_has_grade' => false, 'step' => null, 'free_base' => 0, 'can_override' => false, 'net' => null, 'net_mode' => 'reference', 'can_set_net' => false];
    }

    private function step(int $amount = 220000): array
    {
        return ['id' => 7, 'grade_id' => 3, 'amount' => $amount];
    }

    public function test_graded_position_takes_the_step_amount(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['position_has_grade' => true, 'step' => $this->step(), 'free_base' => 999]));

        $this->assertTrue($r['ok']);
        $this->assertTrue($r['create']);
        $this->assertSame('grid', $r['source']);
        $this->assertSame(220000, $r['base']);
        $this->assertSame(3, $r['grade_id']);
        $this->assertSame(7, $r['step_id']);
        $this->assertFalse($r['override']);
        $this->assertSame('gross', $r['mode']);
    }

    public function test_graded_position_without_step_is_refused(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['position_has_grade' => true]));

        $this->assertFalse($r['ok']);
        $this->assertSame('form.salary_step_id', $r['field']);
        $this->assertSame('Choisissez un échelon pour ce poste.', $r['error']);
    }

    public function test_free_amount_on_graded_position_needs_the_right(): void
    {
        $refused = EmployeeSalaryDraft::resolve($this->in(['position_has_grade' => true, 'free_base' => 300000]));
        $this->assertFalse($refused['ok']);
        $this->assertStringContainsString('catégorie salariale', $refused['error']);

        $allowed = EmployeeSalaryDraft::resolve($this->in(['position_has_grade' => true, 'free_base' => 300000, 'can_override' => true]));
        $this->assertTrue($allowed['ok']);
        $this->assertSame('free', $allowed['source']);
        $this->assertTrue($allowed['override']);
        $this->assertSame(300000, $allowed['base']);
        $this->assertNull($allowed['grade_id']);
        $this->assertNull($allowed['step_id']);
    }

    public function test_position_without_grade_keeps_the_free_amount(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000]));

        $this->assertTrue($r['ok']);
        $this->assertSame('free', $r['source']);
        $this->assertSame(250000, $r['base']);
        $this->assertFalse($r['override']);
        $this->assertTrue($r['create']);
    }

    /** Un salaire de base nul ne crée aucune ligne (comme « Initialiser depuis les contrats »). */
    public function test_zero_salary_creates_no_line(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 0, 'net' => 200000, 'can_set_net' => true]));

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['create']);
        $this->assertNull($r['target_net']);
    }

    public function test_known_net_as_reference_keeps_gross_mode(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000, 'net' => 225000, 'net_mode' => 'reference', 'can_set_net' => true]));

        $this->assertSame('gross', $r['mode']);
        $this->assertSame(225000, $r['target_net']);
    }

    public function test_guaranteed_net_switches_to_net_target_mode(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000, 'net' => 225000, 'net_mode' => 'guarantee', 'can_set_net' => true]));

        $this->assertSame('net_target', $r['mode']);
        $this->assertSame(225000, $r['target_net']);
        $this->assertSame(250000, $r['base']);
    }

    public function test_net_is_ignored_without_the_right(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000, 'net' => 225000, 'net_mode' => 'guarantee', 'can_set_net' => false]));

        $this->assertSame('gross', $r['mode']);
        $this->assertNull($r['target_net']);
    }

    public function test_guarantee_without_a_net_stays_gross(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000, 'net' => null, 'net_mode' => 'guarantee', 'can_set_net' => true]));

        $this->assertSame('gross', $r['mode']);
        $this->assertNull($r['target_net']);
    }

    public function test_unknown_net_mode_falls_back_to_reference(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['free_base' => 250000, 'net' => 225000, 'net_mode' => 'inconnu', 'can_set_net' => true]));

        $this->assertSame('gross', $r['mode']);
        $this->assertSame(225000, $r['target_net']);
    }

    public function test_net_goes_with_a_grid_step_too(): void
    {
        $r = EmployeeSalaryDraft::resolve($this->in(['position_has_grade' => true, 'step' => $this->step(), 'net' => 190000, 'net_mode' => 'guarantee', 'can_set_net' => true]));

        $this->assertSame('net_target', $r['mode']);
        $this->assertSame(220000, $r['base']);
        $this->assertSame(190000, $r['target_net']);
    }
}
