<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private function setup_employee(float $balance): array
    {
        $hr = $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $staff = $this->makeAccount($employee);

        if ($balance != 0.0) {
            app(LeaveBalanceService::class)->post($employee, 'annual', 'adjustment', $balance, today(), 'solde initial');
        }

        LeaveType::ensureDefaults($hr->company_id);
        $this->actingAs($staff);

        return [$employee, LeaveType::where('code', 'annual')->first()];
    }

    private function data(LeaveType $type, int $weekOffset, int $lastDay = 4, array $over = []): array
    {
        $monday = today()->next(Carbon::MONDAY)->addWeeks($weekOffset);

        return array_merge([
            'leave_type_id' => $type->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays($lastDay)->toDateString(),
        ], $over);
    }

    public function test_une_demande_dans_le_solde_est_acceptee_et_reserve_les_jours(): void
    {
        [$employee, $type] = $this->setup_employee(10);

        $leave = app(LeaveService::class)->submit($employee, $this->data($type, 0));

        $this->assertSame('pending', $leave->status);
        $this->assertSame(5.0, $leave->days);
        $this->assertFalse($leave->exceeds_balance);
        $this->assertSame(5.0, app(LeaveBalanceService::class)->available($employee->id)); // 10 − 5 réservés
    }

    public function test_au_dela_de_la_tolerance_la_demande_est_refusee(): void
    {
        [$employee, $type] = $this->setup_employee(3);

        $this->expectException(ValidationException::class);
        // 10 jours demandés pour 3 disponibles : −7, au-delà de la tolérance de 5
        app(LeaveService::class)->submit($employee, $this->data($type, 0, 11));
    }

    public function test_dans_la_tolerance_un_motif_est_obligatoire_puis_la_demande_est_marquee_exception(): void
    {
        [$employee, $type] = $this->setup_employee(3);
        $service = app(LeaveService::class);

        try {
            $service->submit($employee, $this->data($type, 0)); // 5 jours pour 3 : −2, toléré, mais sans motif
            $this->fail('Le motif de l’exception est obligatoire.');
        } catch (ValidationException) {
            $this->assertSame(0, LeaveRequest::count());
        }

        $leave = $service->submit($employee, $this->data($type, 0, 4, ['exception_reason' => 'Mariage de ma sœur, voyage prévu']));

        $this->assertTrue($leave->exceeds_balance);
        $this->assertSame(3.0, $leave->balance_before);
    }

    public function test_la_tolerance_tient_compte_des_demandes_en_attente(): void
    {
        [$employee, $type] = $this->setup_employee(5);
        $service = app(LeaveService::class);

        $service->submit($employee, $this->data($type, 0));      // 5 jours : solde épuisé (0)

        // Deuxième demande de 5 jours : −5, exactement la tolérance : permise avec motif
        $second = $service->submit($employee, $this->data($type, 1, 4, ['exception_reason' => 'Urgence familiale']));
        $this->assertTrue($second->exceeds_balance);

        // Troisième : −10, au-delà de la tolérance
        $this->expectException(ValidationException::class);
        $service->submit($employee, $this->data($type, 2, 4, ['exception_reason' => 'Encore']));
    }

    public function test_deux_demandes_ne_peuvent_pas_se_chevaucher(): void
    {
        [$employee, $type] = $this->setup_employee(20);
        $service = app(LeaveService::class);

        $service->submit($employee, $this->data($type, 0));

        $this->expectException(ValidationException::class);
        $service->submit($employee, $this->data($type, 0, 1)); // lundi-mardi de la même semaine
    }

    public function test_une_date_de_debut_passee_est_refusee_sauf_pour_la_rh(): void
    {
        [$employee, $type] = $this->setup_employee(20);
        $past = today()->subWeeks(2)->next(Carbon::MONDAY);
        $data = ['leave_type_id' => $type->id, 'start_date' => $past->toDateString(), 'end_date' => $past->toDateString()];

        $this->assertNotEmpty(app(LeaveService::class)->evaluate($employee, $type, $data['start_date'], $data['end_date'])['errors']);
        $this->assertEmpty(app(LeaveService::class)->evaluate($employee, $type, $data['start_date'], $data['end_date'], false, false, true)['errors']);
    }

    public function test_un_type_sans_deduction_ne_touche_pas_au_solde(): void
    {
        [$employee] = $this->setup_employee(0);
        $family = LeaveType::where('code', 'family')->first();

        $leave = app(LeaveService::class)->submit($employee, $this->data($family, 0, 1));

        $this->assertSame(2.0, $leave->days);
        $this->assertSame(0.0, app(LeaveBalanceService::class)->available($employee->id));
    }

    public function test_un_justificatif_obligatoire_est_exige(): void
    {
        [$employee] = $this->setup_employee(0);
        $sick = LeaveType::where('code', 'sick')->first();

        $this->expectException(ValidationException::class);
        app(LeaveService::class)->submit($employee, $this->data($sick, 0, 1));
    }

    public function test_les_jours_de_recuperation_n_ont_aucune_tolerance(): void
    {
        [$employee] = $this->setup_employee(0);
        $recovery = LeaveType::where('code', 'recovery')->first();

        $this->expectException(ValidationException::class);
        app(LeaveService::class)->submit($employee, $this->data($recovery, 0, 0));
    }
}
