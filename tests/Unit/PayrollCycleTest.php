<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCycle;
use PHPUnit\Framework\TestCase;

class PayrollCycleTest extends TestCase
{
    private function statuses(array $f): array
    {
        return array_column(PayrollCycle::steps($f)['steps'], 'status', 'key');
    }

    private function delivery(array $over = []): array
    {
        return $over + ['total' => 12, 'sent' => 12, 'partial' => 0, 'failed' => 0, 'not_sent' => 0, 'no_address' => 0, 'seen_app' => 0, 'opened_email' => 0];
    }

    /** Mois pas encore démarré, sans variable en attente : le calcul est l'étape à faire. */
    public function test_new_month_starts_at_calculation(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'none']);

        $this->assertSame('calculation', $c['current']);
        $this->assertSame(['variables' => 'done', 'calculation' => 'current', 'checks' => 'waiting', 'closing' => 'waiting', 'sending' => 'waiting'], array_column($c['steps'], 'status', 'key'));
        $this->assertSame(1, $c['done']);
        $this->assertSame(20, $c['percent']);
        $this->assertFalse($c['finished']);
    }

    public function test_pending_variables_come_first(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'draft', 'pending_variables' => 3]);

        $this->assertSame('variables', $c['current']);
        $this->assertStringContainsString('3 variables à confirmer', $c['steps'][0]['detail']);
    }

    public function test_calculated_with_pending_checks_stops_at_checks(): void
    {
        $s = $this->statuses(['period_status' => 'calculated', 'payslips' => 12, 'pending_checks' => 2]);

        $this->assertSame(['variables' => 'done', 'calculation' => 'done', 'checks' => 'current', 'closing' => 'waiting', 'sending' => 'waiting'], $s);
    }

    public function test_calculated_without_checks_is_ready_to_close(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'calculated', 'payslips' => 12]);

        $this->assertSame('closing', $c['current']);
        $this->assertSame('Prête à être clôturée.', $c['steps'][3]['detail']);
        $this->assertSame(60, $c['percent']);
    }

    /** Une variable proposée après le calcul redonne la main à l'étape variables, avec un rappel de recalcul. */
    public function test_new_variable_after_calculation_reopens_variables(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'calculated', 'payslips' => 12, 'pending_variables' => 1]);

        $this->assertSame('variables', $c['current']);
        $this->assertStringContainsString('Recalculez', $c['steps'][0]['detail']);
        $this->assertSame('done', $c['steps'][1]['status']);
    }

    public function test_closed_with_failed_deliveries_stops_at_sending(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'closed', 'payslips' => 12, 'delivery' => $this->delivery(['sent' => 9, 'failed' => 2, 'not_sent' => 1])]);

        $this->assertSame('sending', $c['current']);
        $this->assertSame('3 bulletins à envoyer ou à relancer dont 2 en échec.', $c['steps'][4]['detail']);
        $this->assertSame(80, $c['percent']);
    }

    public function test_everything_sent_finishes_the_cycle(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'closed', 'payslips' => 12, 'delivery' => $this->delivery(['sent' => 11, 'no_address' => 1])]);

        $this->assertTrue($c['finished']);
        $this->assertNull($c['current']);
        $this->assertSame(100, $c['percent']);
        $this->assertSame('Tous les bulletins envoyables sont envoyés (1 employé sans adresse e-mail).', $c['steps'][4]['detail']);
    }

    public function test_closed_without_any_payslip_is_not_finished(): void
    {
        $c = PayrollCycle::steps(['period_status' => 'closed', 'payslips' => 0, 'delivery' => null]);

        $this->assertFalse($c['finished']);
        $this->assertSame('sending', $c['current']);
    }

    public function test_every_step_has_a_target(): void
    {
        foreach (PayrollCycle::steps([])['steps'] as $s) {
            $this->assertNotEmpty($s['target']['route']);
            $this->assertNotEmpty($s['target']['resource']);
            $this->assertNotEmpty($s['target']['label']);
        }
    }
}
