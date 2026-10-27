<?php

namespace Tests\Feature;

use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class LeaveBalanceTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    public function test_l_acquisition_part_de_la_date_d_embauche_et_ne_double_jamais(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee(['hire_date' => today()->subMonths(3)->toDateString()]);
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee);
        $balances->sync($employee);

        $this->assertSame(7.5, $balances->balance($employee->id)); // 3 mois × 2,5 j
    }

    public function test_la_cadence_saisie_a_la_creation_est_utilisee(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee(['hire_date' => today()->subMonths(2)->toDateString(), 'leave_days_per_month' => 3]);
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee);

        $this->assertSame(6.0, $balances->balance($employee->id));
    }

    public function test_un_employe_embauche_aujourd_hui_n_a_encore_rien_acquis(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee);

        $this->assertSame(0.0, $balances->balance($employee->id));
    }

    public function test_les_jours_de_recuperation_perimes_expirent(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);

        $balances->post($employee, 'recovery', 'recovery_credit', 1, today()->subDays(100), 'périmé', null, today()->subDay()->toDateString());
        $balances->post($employee, 'recovery', 'recovery_credit', 1, today(), 'valide', null, today()->addDays(30)->toDateString());

        $balances->sync($employee);
        $balances->sync($employee); // idempotent

        $this->assertSame(1.0, $balances->balance($employee->id, 'recovery'));
    }

    public function test_les_jours_deja_pris_ne_sont_pas_expires(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);

        $balances->post($employee, 'recovery', 'recovery_credit', 1, today()->subDays(100), 'crédit', null, today()->subDay()->toDateString());
        $balances->post($employee, 'recovery', 'adjustment', -1, today()->subDays(50), 'jour pris');

        $balances->sync($employee);

        $this->assertSame(0.0, $balances->balance($employee->id, 'recovery'));
    }
}
