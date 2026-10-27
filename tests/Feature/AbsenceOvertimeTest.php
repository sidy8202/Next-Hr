<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\OvertimeRecord;
use App\Services\AbsenceService;
use App\Services\ApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class AbsenceOvertimeTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    public function test_une_absence_non_justifiee_est_retiree_du_solde_puis_rendue_quand_elle_est_justifiee(): void
    {
        $hr = $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);
        $balances->post($employee, 'annual', 'adjustment', 10, today(), 'solde initial');

        $monday = today()->previous(Carbon::MONDAY);
        $absence = app(AbsenceService::class)->record($employee, $monday->toDateString(), $monday->copy()->addDays(1)->toDateString(), false, 'Pas de nouvelles');

        $this->assertSame(2.0, $absence->days);
        $this->assertSame(8.0, $balances->balance($employee->id));

        app(AbsenceService::class)->justify($absence);

        $this->assertTrue($absence->fresh()->justified);
        $this->assertSame(10.0, $balances->balance($employee->id));
    }

    public function test_une_absence_justifiee_ne_retire_rien(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);
        $balances->post($employee, 'annual', 'adjustment', 10, today(), 'solde initial');

        $monday = today()->previous(Carbon::MONDAY);
        app(AbsenceService::class)->record($employee, $monday->toDateString(), $monday->toDateString(), true, 'Certificat médical');

        $this->assertSame(10.0, $balances->balance($employee->id));
    }

    public function test_la_suppression_d_une_absence_rend_les_jours(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $balances = app(LeaveBalanceService::class);
        $balances->post($employee, 'annual', 'adjustment', 10, today(), 'solde initial');

        $monday = today()->previous(Carbon::MONDAY);
        $absence = app(AbsenceService::class)->record($employee, $monday->toDateString(), $monday->toDateString(), false);
        app(AbsenceService::class)->delete($absence);

        $this->assertSame(10.0, $balances->balance($employee->id));
        $this->assertSame(0, Absence::count());
    }

    public function test_un_jour_ouvre_normal_n_est_pas_compensable(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();

        $this->expectException(ValidationException::class);
        app(OvertimeService::class)->declare($employee, today()->previous(Carbon::WEDNESDAY)->toDateString(), 1, 'Dossier urgent');
    }

    public function test_un_dimanche_travaille_donne_un_jour_de_recuperation_avec_date_limite(): void
    {
        $hr = $this->makeCompanyContext();
        $employee = $this->makeEmployee(); // pas de chef : étape « chef » sautée
        $sunday = today()->previous(Carbon::SUNDAY)->toDateString();

        $record = app(OvertimeService::class)->declare($employee, $sunday, 1, 'Inventaire annuel');
        $step = \App\Models\ApprovalStep::where('approvable_type', 'overtime')->where('approvable_id', $record->id)->where('kind', 'hr')->first();

        app(ApprovalService::class)->decide($step->id, $hr, 'approve', null, ['compensation' => 'recovery', 'credited_days' => 1]);

        $record = $record->fresh();
        $this->assertSame('approved', $record->status);
        $this->assertSame('recovery', $record->compensation);
        $this->assertNotNull($record->expires_on);

        $balances = app(LeaveBalanceService::class);
        $this->assertSame(1.0, $balances->balance($employee->id, 'recovery'));
        $this->assertSame(0.0, $balances->balance($employee->id, 'annual'));
    }

    public function test_la_rh_peut_ajouter_le_jour_travaille_directement_au_solde_de_conge(): void
    {
        $hr = $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $sunday = today()->previous(Carbon::SUNDAY)->toDateString();

        $record = app(OvertimeService::class)->declare($employee, $sunday, 0.5, null);
        $step = \App\Models\ApprovalStep::where('approvable_type', 'overtime')->where('approvable_id', $record->id)->where('kind', 'hr')->first();

        app(ApprovalService::class)->decide($step->id, $hr, 'approve', null, ['compensation' => 'leave_balance', 'credited_days' => 0.5]);

        $this->assertSame(0.5, app(LeaveBalanceService::class)->balance($employee->id, 'annual'));
    }

    public function test_sans_choix_de_compensation_la_validation_est_refusee(): void
    {
        $hr = $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $record = app(OvertimeService::class)->declare($employee, today()->previous(Carbon::SUNDAY)->toDateString(), 1, null);
        $step = \App\Models\ApprovalStep::where('approvable_type', 'overtime')->where('approvable_id', $record->id)->where('kind', 'hr')->first();

        $this->expectException(ValidationException::class);
        app(ApprovalService::class)->decide($step->id, $hr, 'approve', null, []);
    }

    public function test_un_meme_jour_travaille_ne_se_declare_pas_deux_fois(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $sunday = today()->previous(Carbon::SUNDAY)->toDateString();
        $service = app(OvertimeService::class);

        $service->declare($employee, $sunday, 1, null);

        $this->expectException(ValidationException::class);
        $service->declare($employee, $sunday, 1, null);
    }
}
