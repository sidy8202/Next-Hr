<?php

namespace Tests\Feature;

use App\Mail\PermissionApprovedMail;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PermissionType;
use App\Services\ApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private function world(float $balance = 10): array
    {
        $hr = $this->makeCompanyContext();
        $manager = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $manager->id]);
        $staff = $this->makeEmployee();
        $uManager = $this->makeAccount($manager);
        $uStaff = $this->makeAccount($staff);
        PermissionType::ensureDefaults($hr->company_id);
        LeaveType::ensureDefaults($hr->company_id);

        if ($balance != 0.0) {
            app(LeaveBalanceService::class)->post($staff, 'annual', 'adjustment', $balance, today(), 'solde initial');
        }

        return [$hr, $uManager, $uStaff, $staff];
    }

    private function type(string $code): PermissionType
    {
        return PermissionType::where('code', $code)->firstOrFail();
    }

    private function week(int $offset = 0, int $lastDay = 4): array
    {
        $monday = today()->next(Carbon::MONDAY)->addWeeks($offset);

        return ['start_date' => $monday->toDateString(), 'end_date' => $monday->copy()->addDays($lastDay)->toDateString()];
    }

    public function test_au_dela_du_quota_gratuit_l_excedent_est_deduit_du_conge(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);

        $ev = app(PermissionService::class)->evaluate($staff, $this->type('personal'), $this->week()); // 5 jours, 2 gratuits

        $this->assertSame(5.0, $ev['days']);
        $this->assertSame(2.0, $ev['free']);
        $this->assertSame(3.0, $ev['deducted']);
        $this->assertSame([], $ev['errors']);
    }

    public function test_dans_le_quota_rien_n_est_deduit(): void
    {
        [, , $uStaff, $staff] = $this->world(0);
        $this->actingAs($uStaff);

        $ev = app(PermissionService::class)->evaluate($staff, $this->type('wedding'), $this->week(0, 2)); // 3 jours, 3 gratuits

        $this->assertSame(0.0, $ev['deducted']);
        $this->assertSame([], $ev['errors']);
    }

    public function test_la_regle_de_l_excedent_est_parametrable(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);
        $service = app(PermissionService::class);

        $this->type('personal')->update(['excess_policy' => 'refuse']);
        $this->assertNotEmpty($service->evaluate($staff, $this->type('personal'), $this->week())['errors']);

        $this->type('personal')->update(['excess_policy' => 'unpaid']);
        $ev = $service->evaluate($staff, $this->type('personal'), $this->week());
        $this->assertSame(3.0, $ev['unpaid']);
        $this->assertSame(0.0, $ev['deducted']);

        $this->type('personal')->update(['excess_policy' => 'deduct_leave', 'free_days' => 4]); // quota modifié
        $this->assertSame(1.0, $service->evaluate($staff, $this->type('personal'), $this->week())['deducted']);
    }

    public function test_l_excedent_respecte_le_solde_et_la_tolerance(): void
    {
        [, , $uStaff, $staff] = $this->world(0);
        $this->actingAs($uStaff);

        // 5 jours demandés, 2 gratuits : 3 j à déduire d'un solde de 0 → −3, dans la tolérance de 5 : exception
        $ev = app(PermissionService::class)->evaluate($staff, $this->type('personal'), $this->week());
        $this->assertTrue($ev['exception']);

        // 10 jours : 8 j à déduire → −8, au-delà de la tolérance : refusé
        $this->assertNotEmpty(app(PermissionService::class)->evaluate($staff, $this->type('personal'), $this->week(0, 9))['errors']);
    }

    public function test_parcours_complet_chef_puis_rh_deduit_l_excedent_et_genere_la_fiche(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);

        $request = app(PermissionService::class)->submit($staff, ['permission_type_id' => $this->type('personal')->id] + $this->week());
        $this->assertSame(3.0, $request->deducted_days);

        $approvals = app(ApprovalService::class);
        $this->actingAs($uManager);
        $approvals->decide($request->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');
        $this->actingAs($hr);
        $approvals->decide($request->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');

        $request = $request->fresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame(7.0, app(LeaveBalanceService::class)->balance($staff->id)); // 10 − 3
        $this->assertSame('permission_sheet', $request->document->type);
        Mail::assertSent(PermissionApprovedMail::class);
        $this->assertGreaterThan(0, $uStaff->notifications()->count());
    }

    public function test_annuler_une_permission_validee_rend_les_jours(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$hr, $uManager, $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);
        $request = app(PermissionService::class)->submit($staff, ['permission_type_id' => $this->type('personal')->id] + $this->week());

        $approvals = app(ApprovalService::class);
        $this->actingAs($uManager);
        $approvals->decide($request->steps()->where('kind', 'manager')->first()->id, $uManager, 'approve');
        $this->actingAs($hr);
        $approvals->decide($request->steps()->where('kind', 'hr')->first()->id, $hr, 'approve');

        app(PermissionService::class)->cancel($request->fresh(), $hr);

        $this->assertSame(10.0, app(LeaveBalanceService::class)->balance($staff->id));
    }

    public function test_les_heures_ont_un_quota_mensuel_gratuit(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);
        $service = app(PermissionService::class);
        $day = today()->next(Carbon::MONDAY)->toDateString();

        $first = $service->submit($staff, ['permission_type_id' => $this->type('hours')->id, 'start_date' => $day, 'start_time' => '09:00', 'end_time' => '12:00']);
        $this->assertSame(0.0, $first->deducted_days); // 3 h sur 8 h gratuites

        // 7 h de plus : 10 h au total, 2 h au-delà du quota = 0,25 jour retiré du congé
        $ev = $service->evaluate($staff, $this->type('hours'), ['start_date' => today()->next(Carbon::MONDAY)->addDay()->toDateString(), 'start_time' => '08:00', 'end_time' => '15:00']);
        $this->assertSame(0.25, $ev['deducted']);
    }

    public function test_le_plafond_annuel_est_applique(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);
        $service = app(PermissionService::class);
        $this->type('wedding')->update(['yearly_cap_days' => 3]);

        $service->submit($staff, ['permission_type_id' => $this->type('wedding')->id] + $this->week(0, 2)); // 3 jours

        $this->expectException(ValidationException::class);
        $service->submit($staff, ['permission_type_id' => $this->type('wedding')->id] + $this->week(1, 0));
    }

    public function test_une_permission_et_un_conge_ne_se_chevauchent_pas(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);
        $week = $this->week();

        LeaveRequest::create(['employee_id' => $staff->id, 'leave_type_id' => LeaveType::where('code', 'annual')->first()->id,
            'start_date' => $week['start_date'], 'end_date' => $week['end_date'], 'days' => 5, 'status' => 'pending']);

        $this->expectException(ValidationException::class);
        app(PermissionService::class)->submit($staff, ['permission_type_id' => $this->type('personal')->id] + $week);
    }

    public function test_un_justificatif_obligatoire_est_exige(): void
    {
        [, , $uStaff, $staff] = $this->world(10);
        $this->actingAs($uStaff);

        $this->expectException(ValidationException::class);
        app(PermissionService::class)->submit($staff, ['permission_type_id' => $this->type('death_parent')->id] + $this->week(0, 1));
    }
}
