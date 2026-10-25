<?php

namespace Tests\Feature;

use App\Livewire\Leaves\Balances;
use App\Models\Delegation;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Services\ApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveService;
use App\Support\LeaveVisibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class LeaveAccessTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private function grant(\App\Models\User $user, string $resource, string $letters, string $scope): void
    {
        Permission::forceCreate([
            'user_id' => $user->id, 'resource' => $resource, 'scope' => $scope,
            'can_create' => str_contains($letters, 'c'), 'can_read' => str_contains($letters, 'r'),
            'can_update' => str_contains($letters, 'u'), 'can_delete' => str_contains($letters, 'd'),
        ]);
    }

    public function test_un_droit_modifier_limite_a_mes_donnees_ne_donne_pas_acces_aux_soldes_de_tous(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $user = $this->makeAccount($employee);
        $this->grant($user, 'leave_requests', 'cru', 'own');

        $this->assertFalse(LeaveVisibility::isHr($user, 'leave_requests'));
        Livewire::actingAs($user)->test(Balances::class)->assertForbidden();
    }

    public function test_la_rh_avec_la_portee_tout_accede_aux_soldes(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee();
        $user = $this->makeAccount($employee);
        $this->grant($user, 'leave_requests', 'cru', 'all');

        $this->assertTrue(LeaveVisibility::isHr($user, 'leave_requests'));
        Livewire::actingAs($user)->test(Balances::class)->assertOk();
    }

    public function test_un_simple_employe_n_accede_pas_aux_soldes_ni_ne_valide_a_l_etape_rh(): void
    {
        $hr = $this->makeCompanyContext();
        $manager = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $manager->id]);
        $staff = $this->makeEmployee();
        $uStaff = $this->makeAccount($staff);
        $this->grant($uStaff, 'leave_requests', 'cr', 'own');

        Livewire::actingAs($uStaff)->test(Balances::class)->assertForbidden();

        LeaveType::ensureDefaults($hr->company_id);
        $type = LeaveType::where('code', 'family')->first();
        $monday = today()->next(Carbon::MONDAY);
        $this->actingAs($uStaff);
        $leave = app(LeaveService::class)->submit($staff, ['leave_type_id' => $type->id, 'start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        // Un autre employé « modifier / mon équipe » ne peut pas valider à l'étape RH
        $other = $this->makeEmployee();
        $uOther = $this->makeAccount($other);
        $this->grant($uOther, 'leave_requests', 'cru', 'team');
        $this->actingAs($uOther);
        $this->assertSame(0, app(ApprovalService::class)->pendingFor($uOther)->count());
    }

    public function test_le_module_delegations_donne_la_main_a_l_interimaire_du_chef(): void
    {
        $hr = $this->makeCompanyContext();
        $manager = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $manager->id]);
        $staff = $this->makeEmployee();
        $uStaff = $this->makeAccount($staff);
        $deputy = $this->makeEmployee();
        $uDeputy = $this->makeAccount($deputy);

        Delegation::forceCreate([
            'company_id' => $hr->company_id, 'from_employee_id' => $manager->id, 'to_employee_id' => $deputy->id,
            'start_date' => today()->subDay()->toDateString(), 'end_date' => today()->addDay()->toDateString(), 'reason' => 'Mission',
        ]);

        LeaveType::ensureDefaults($hr->company_id);
        $type = LeaveType::where('code', 'family')->first();
        $monday = today()->next(Carbon::MONDAY);
        $this->actingAs($uStaff);
        app(LeaveService::class)->submit($staff, ['leave_type_id' => $type->id, 'start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->actingAs($uDeputy);
        $items = app(ApprovalService::class)->pendingFor($uDeputy);

        $this->assertSame(1, $items->count());
        $this->assertSame('delegate', $items->first()['capacity']);
    }
}
