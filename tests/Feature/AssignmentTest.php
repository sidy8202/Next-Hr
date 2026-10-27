<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\WorkSite;
use App\Services\ApprovalService;
use App\Services\AssignmentService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private Department $finance;
    private Position $financeHead;

    private function world(): array
    {
        $hr = $this->makeCompanyContext();
        $this->finance = Department::create(['name' => 'Finance', 'code' => 'FIN']);
        $this->financeHead = Position::create(['department_id' => $this->finance->id, 'title' => 'Chef Finance']);

        return [$hr];
    }

    private function data(array $over = []): array
    {
        return array_merge([
            'type' => 'interim', 'department_id' => $this->finance->id, 'position_id' => $this->financeHead->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->addDays(14)->toDateString(),
        ], $over);
    }

    public function test_un_employe_peut_occuper_un_poste_dans_un_autre_departement_et_une_autre_base(): void
    {
        $this->world();
        $employee = $this->makeEmployee();
        $sikasso = WorkSite::create(['name' => 'Base de Sikasso', 'city' => 'Sikasso']);

        $a = app(AssignmentService::class)->create($employee, $this->data(['type' => 'secondment', 'work_site_id' => $sikasso->id, 'allocation_percent' => 50]));

        $this->assertSame($this->finance->id, $a->department_id);
        $this->assertNotSame($employee->department_id, $a->department_id); // son poste principal reste dans l'autre département
        $this->assertSame(1, app(AssignmentService::class)->activeFor($employee->id)->count());
        $this->assertSame('Base de Sikasso', app(AssignmentService::class)->activeFor($employee->id)->first()->site->name);
    }

    public function test_le_poste_doit_appartenir_au_departement(): void
    {
        $this->world();
        $employee = $this->makeEmployee();

        $this->expectException(ValidationException::class);
        app(AssignmentService::class)->create($employee, $this->data(['position_id' => $this->position->id])); // poste d'Informatique dans Finance
    }

    public function test_pas_de_doublon_sur_la_meme_periode(): void
    {
        $this->world();
        $employee = $this->makeEmployee();
        $service = app(AssignmentService::class);
        $service->create($employee, $this->data());

        $this->expectException(ValidationException::class);
        $service->create($employee, $this->data(['start_date' => today()->addDays(3)->toDateString()]));
    }

    public function test_celui_qui_fait_fonction_de_chef_valide_a_sa_place_pendant_l_interim(): void
    {
        [$hr] = $this->world();
        $head = $this->makeEmployee(['department_id' => $this->finance->id, 'position_id' => $this->financeHead->id]);
        Department::whereKey($this->finance->id)->update(['head_id' => $head->id]);
        $member = $this->makeEmployee(['department_id' => $this->finance->id, 'position_id' => $this->financeHead->id]); // équipe Finance
        $uMember = $this->makeAccount($member);

        $acting = $this->makeEmployee(); // vient du département Informatique
        $uActing = $this->makeAccount($acting);
        app(AssignmentService::class)->create($acting, $this->data(['acts_as_head' => true]));

        LeaveType::ensureDefaults($hr->company_id);
        app(LeaveBalanceService::class)->post($member, 'annual', 'adjustment', 10, today(), 'solde initial');
        $monday = today()->next(Carbon::MONDAY);
        $this->actingAs($uMember);
        app(LeaveService::class)->submit($member, ['leave_type_id' => LeaveType::where('code', 'annual')->first()->id, 'start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->actingAs($uActing);
        $items = app(ApprovalService::class)->pendingFor($uActing);

        $this->assertSame(1, $items->count());
        $this->assertSame('delegate', $items->first()['capacity']);
    }

    public function test_une_affectation_terminee_ne_donne_plus_la_main(): void
    {
        [$hr] = $this->world();
        $head = $this->makeEmployee(['department_id' => $this->finance->id, 'position_id' => $this->financeHead->id]);
        Department::whereKey($this->finance->id)->update(['head_id' => $head->id]);
        $member = $this->makeEmployee(['department_id' => $this->finance->id, 'position_id' => $this->financeHead->id]);
        $uMember = $this->makeAccount($member);
        $acting = $this->makeEmployee();
        $uActing = $this->makeAccount($acting);

        $a = app(AssignmentService::class)->create($acting, $this->data(['acts_as_head' => true, 'start_date' => today()->subDays(10)->toDateString(), 'end_date' => today()->subDay()->toDateString()]));
        $this->assertSame('past', $a->state());

        LeaveType::ensureDefaults($hr->company_id);
        app(LeaveBalanceService::class)->post($member, 'annual', 'adjustment', 10, today(), 'solde initial');
        $monday = today()->next(Carbon::MONDAY);
        $this->actingAs($uMember);
        app(LeaveService::class)->submit($member, ['leave_type_id' => LeaveType::where('code', 'annual')->first()->id, 'start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->actingAs($uActing);
        $this->assertSame(0, app(ApprovalService::class)->pendingFor($uActing)->count());
    }

    public function test_le_superieur_de_l_affectation_valide_aussi_les_conges_de_l_employe_detache(): void
    {
        [$hr] = $this->world();
        $head = $this->makeEmployee();
        Department::whereKey($this->dept->id)->update(['head_id' => $head->id]);
        $seconded = $this->makeEmployee();
        $projectLead = $this->makeEmployee(['department_id' => $this->finance->id, 'position_id' => $this->financeHead->id]);
        $uLead = $this->makeAccount($projectLead);
        $uSeconded = $this->makeAccount($seconded);

        app(AssignmentService::class)->create($seconded, $this->data(['type' => 'secondment', 'reports_to_employee_id' => $projectLead->id]));

        LeaveType::ensureDefaults($hr->company_id);
        app(LeaveBalanceService::class)->post($seconded, 'annual', 'adjustment', 10, today(), 'solde initial');
        $monday = today()->next(Carbon::MONDAY);
        $this->actingAs($uSeconded);
        app(LeaveService::class)->submit($seconded, ['leave_type_id' => LeaveType::where('code', 'annual')->first()->id, 'start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->actingAs($uLead);
        $this->assertSame(1, app(ApprovalService::class)->pendingFor($uLead)->count());
    }

    public function test_la_prime_mensuelle_est_proratisee_au_jour(): void
    {
        $this->world();
        $employee = $this->makeEmployee();
        $month = today()->startOfMonth();

        app(AssignmentService::class)->create($employee, $this->data([
            'start_date' => $month->toDateString(), 'end_date' => $month->copy()->addDays(14)->toDateString(), // 15 premiers jours du mois
            'allowance_amount' => 50000, 'allowance_basis' => 'monthly',
        ]));

        $lines = app(AssignmentService::class)->allowanceLines($employee, $month);

        $this->assertCount(1, $lines);
        $this->assertSame(15, $lines[0]['days']);
        $this->assertEqualsWithDelta(50000 * 15 / $month->daysInMonth, $lines[0]['amount'], 0.01);
    }

    public function test_une_prime_totale_est_repartie_sur_la_duree_de_l_affectation(): void
    {
        $this->world();
        $employee = $this->makeEmployee();
        $month = today()->startOfMonth();

        // 20 jours dont 10 dans le mois courant (du 11e jour du mois précédent au 10e du courant)
        app(AssignmentService::class)->create($employee, $this->data([
            'start_date' => $month->copy()->subDays(10)->toDateString(), 'end_date' => $month->copy()->addDays(9)->toDateString(),
            'allowance_amount' => 100000, 'allowance_basis' => 'total',
        ]));

        $lines = app(AssignmentService::class)->allowanceLines($employee, $month);

        $this->assertSame(10, $lines[0]['days']);
        $this->assertEqualsWithDelta(100000 * 10 / 20, $lines[0]['amount'], 0.01);
    }

    public function test_une_prime_totale_exige_une_date_de_fin(): void
    {
        $this->world();
        $employee = $this->makeEmployee();

        $this->expectException(ValidationException::class);
        app(AssignmentService::class)->create($employee, $this->data(['end_date' => null, 'allowance_amount' => 10000, 'allowance_basis' => 'total']));
    }

    public function test_mettre_fin_a_une_affectation(): void
    {
        $this->world();
        $employee = $this->makeEmployee();
        $a = app(AssignmentService::class)->create($employee, $this->data(['end_date' => null]));

        app(AssignmentService::class)->end($a);

        $this->assertSame(today()->toDateString(), $a->fresh()->end_date->toDateString());
    }
}
