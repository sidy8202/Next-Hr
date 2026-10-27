<?php

namespace Tests\Feature;

use App\Mail\LeaveApprovedMail;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\ApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    /** @return array{0: \App\Models\User, 1: \App\Models\Employee, 2: \App\Models\User, 3: \App\Models\Employee, 4: \App\Models\User, 5: LeaveType} */
    private function world(): array
    {
        $hr = $this->makeCompanyContext();
        $manager = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $manager->id]);
        $staff = $this->makeEmployee(); // son manager = le chef du département

        $uManager = $this->makeAccount($manager);
        $uStaff = $this->makeAccount($staff);

        LeaveType::ensureDefaults($hr->company_id);
        app(LeaveBalanceService::class)->post($staff, 'annual', 'adjustment', 20, today(), 'solde initial');

        return [$uManager, $manager, $uStaff, $staff, $hr, LeaveType::where('code', 'annual')->first()];
    }

    private function request(\App\Models\User $as, \App\Models\Employee $employee, LeaveType $type, int $lastDay = 4, array $over = []): LeaveRequest
    {
        $this->actingAs($as);
        $monday = today()->next(Carbon::MONDAY);

        return app(LeaveService::class)->submit($employee, array_merge([
            'leave_type_id' => $type->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays($lastDay)->toDateString(),
        ], $over));
    }

    public function test_chef_puis_rh_valident_et_la_fiche_de_conge_est_generee_et_envoyee(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$uManager, , $uStaff, $staff, $hr, $type] = $this->world();

        $leave = $this->request($uStaff, $staff, $type);
        $this->assertSame('pending', $leave->status);
        $this->assertCount(2, $leave->steps);

        $approvals = app(ApprovalService::class);

        $this->actingAs($uManager);
        $approvals->decide($leave->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');
        $this->assertSame('pending', $leave->fresh()->status); // il reste la RH

        $this->actingAs($hr);
        $approvals->decide($leave->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');

        $leave = $leave->fresh();
        $this->assertSame('approved', $leave->status);
        $this->assertSame(15.0, app(LeaveBalanceService::class)->balance($staff->id)); // 20 − 5
        $this->assertNotNull($leave->document_id);
        $this->assertSame('leave_sheet', $leave->document->type);
        Mail::assertSent(LeaveApprovedMail::class);
        $this->assertGreaterThan(0, $uStaff->notifications()->count()); // l'employé est notifié
    }

    public function test_personne_ne_valide_sa_propre_demande(): void
    {
        [$uManager, $manager, , , $hr, $type] = $this->world();
        app(LeaveBalanceService::class)->post($manager, 'annual', 'adjustment', 20, today(), 'solde initial');

        $leave = $this->request($uManager, $manager, $type, 1); // le chef n'a pas de chef : étape « chef » sautée

        $this->assertSame('skipped', $leave->steps()->where('kind', 'manager')->first()->status);
        $this->actingAs($uManager);
        $this->assertSame(0, app(ApprovalService::class)->pendingFor($uManager)->count());
        $this->actingAs($hr);
        $this->assertSame(1, app(ApprovalService::class)->pendingFor($hr)->count());
    }

    public function test_le_chef_ne_peut_pas_sauter_l_etape_rh(): void
    {
        [$uManager, , $uStaff, $staff, , $type] = $this->world();
        $leave = $this->request($uStaff, $staff, $type);

        $this->actingAs($uManager);
        $this->expectException(ValidationException::class);
        app(ApprovalService::class)->decide($leave->steps()->where('kind', 'hr')->first()->id, $uManager, 'approve');
    }

    public function test_un_refus_exige_un_motif_et_annule_la_suite(): void
    {
        [$uManager, , $uStaff, $staff, , $type] = $this->world();
        $leave = $this->request($uStaff, $staff, $type);
        $step = $leave->steps()->where('kind', 'manager')->first();

        $this->actingAs($uManager);

        try {
            app(ApprovalService::class)->decide($step->id, $uManager, 'reject', '');
            $this->fail('Le motif du refus est obligatoire.');
        } catch (ValidationException) {
            $this->assertSame('pending', $leave->fresh()->status);
        }

        app(ApprovalService::class)->decide($step->id, $uManager, 'reject', 'Période de clôture comptable');

        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertSame('cancelled', $leave->steps()->where('kind', 'hr')->first()->status);
        $this->assertSame(20.0, app(LeaveBalanceService::class)->balance($staff->id)); // rien n'est retiré
    }

    public function test_l_interimaire_valide_pendant_le_conge_du_chef(): void
    {
        [, $manager, $uStaff, $staff, , $type] = $this->world();
        $interim = $this->makeEmployee();
        $uInterim = $this->makeAccount($interim);

        LeaveRequest::create([
            'employee_id' => $manager->id, 'leave_type_id' => $type->id,
            'start_date' => today()->subDay()->toDateString(), 'end_date' => today()->addDays(3)->toDateString(),
            'days' => 3, 'status' => 'approved', 'interim_employee_id' => $interim->id,
        ]);

        $this->request($uStaff, $staff, $type);

        $this->actingAs($uInterim);
        $items = app(ApprovalService::class)->pendingFor($uInterim);

        $this->assertSame(1, $items->count());
        $this->assertSame('delegate', $items->first()['capacity']);
    }

    public function test_la_rh_peut_valider_a_la_place_du_chef(): void
    {
        [, , $uStaff, $staff, $hr, $type] = $this->world();
        $this->request($uStaff, $staff, $type);

        $this->actingAs($hr);
        $items = app(ApprovalService::class)->pendingFor($hr);

        $this->assertSame('hr_override', $items->first()['capacity']);
    }

    public function test_le_solde_est_reverifie_a_la_validation_finale(): void
    {
        [$uManager, , $uStaff, $staff, $hr, $type] = $this->world();
        $leave = $this->request($uStaff, $staff, $type, 4); // 5 jours, solde 20

        $approvals = app(ApprovalService::class);
        $this->actingAs($uManager);
        $approvals->decide($leave->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');

        // Entre-temps, le solde baisse à 1 j : après ce congé de 5 j il serait de −4, dans la tolérance (5) : la validation passe
        $this->actingAs($hr);
        app(LeaveService::class)->adjust($staff, 'annual', -19, 'correction de test');
        $approvals->decide($leave->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_le_solde_trop_bas_a_la_validation_bloque_l_approbation(): void
    {
        [$uManager, , $uStaff, $staff, $hr, $type] = $this->world();
        $leave = $this->request($uStaff, $staff, $type, 4);

        $approvals = app(ApprovalService::class);
        $this->actingAs($uManager);
        $approvals->decide($leave->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');

        $this->actingAs($hr);
        app(LeaveService::class)->adjust($staff, 'annual', -25, 'correction de test'); // solde −5 → après congé −10 : hors tolérance

        $this->expectException(ValidationException::class);
        $approvals->decide($leave->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');
    }

    public function test_l_annulation_d_un_conge_valide_rend_les_jours(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$uManager, , $uStaff, $staff, $hr, $type] = $this->world();
        $leave = $this->request($uStaff, $staff, $type);
        $approvals = app(ApprovalService::class);

        $this->actingAs($uManager);
        $approvals->decide($leave->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');
        $this->actingAs($hr);
        $approvals->decide($leave->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');
        $this->assertSame(15.0, app(LeaveBalanceService::class)->balance($staff->id));

        app(LeaveService::class)->cancel($leave->fresh(), $hr);

        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertSame(20.0, app(LeaveBalanceService::class)->balance($staff->id));
    }
}
