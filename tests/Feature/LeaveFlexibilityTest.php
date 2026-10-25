<?php

namespace Tests\Feature;

use App\Livewire\Leaves\LeaveSettingsPage;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveSetting;
use App\Models\WorkSchedule;
use App\Services\LeaveBalanceService;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\LeaveFixtures;
use Tests\TestCase;

class LeaveFlexibilityTest extends TestCase
{
    use RefreshDatabase, LeaveFixtures;

    private function rotation(int $on = 14, int $off = 14, ?Carbon $anchor = null): WorkSchedule
    {
        return WorkSchedule::create(['name' => 'Rotation A', 'type' => 'rotation', 'on_days' => $on, 'off_days' => $off, 'anchor_date' => ($anchor ?? today()->next(Carbon::MONDAY))->toDateString()]);
    }

    public function test_par_defaut_les_jours_se_comptent_en_jours_calendaires(): void
    {
        $user = $this->makeUser();
        $monday = today()->next(Carbon::MONDAY);

        // Du lundi au dimanche : 7 jours (week-end compris), comme les congés annuels du Code du travail malien
        $this->assertSame(7.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(6)));
    }

    public function test_le_mode_calendaire_sans_jours_feries_les_retire(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        LeaveSetting::for($user->company_id)->update(['count_mode' => 'calendar_no_holidays']);
        $monday = today()->next(Carbon::MONDAY);
        Holiday::create(['date' => $monday->copy()->addDays(2)->toDateString(), 'name' => 'Fête']);

        $this->assertSame(6.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(6)));
    }

    public function test_une_rotation_ne_decompte_que_les_jours_de_travail_prevus(): void
    {
        $this->makeCompanyContext();
        $schedule = $this->rotation(14, 14);
        $employee = $this->makeEmployee();
        Employee::whereKey($employee->id)->update(['work_schedule_id' => $schedule->id]);
        $employee = $employee->fresh();
        LeaveSetting::for($employee->company_id)->update(['count_mode' => 'scheduled']);

        $anchor = $schedule->anchor_date->copy();

        // 28 jours = un cycle complet : 14 jours de travail décomptés
        $this->assertSame(14.0, LeaveCalendar::countDays($employee->company_id, $anchor, $anchor->copy()->addDays(27), false, false, $employee));
        // Une semaine dans la période de repos : rien à décompter
        $this->assertSame(0.0, LeaveCalendar::countDays($employee->company_id, $anchor->copy()->addDays(15), $anchor->copy()->addDays(21), false, false, $employee));
        // Le cycle suivant recommence
        $this->assertTrue(LeaveCalendar::isScheduled($schedule, $anchor->copy()->addDays(28), []));
        $this->assertFalse(LeaveCalendar::isScheduled($schedule, $anchor->copy()->addDays(14), []));
    }

    public function test_travailler_pendant_la_periode_de_repos_d_une_rotation_est_compensable(): void
    {
        $this->makeCompanyContext();
        $schedule = $this->rotation(14, 14, today()->subDays(40));
        $employee = $this->makeEmployee();
        Employee::whereKey($employee->id)->update(['work_schedule_id' => $schedule->id]);
        $employee = $employee->fresh();

        $offDay = $schedule->anchor_date->copy()->addDays(20); // pendant la période de repos du 1er cycle (jours 14 à 27)

        $this->assertTrue(LeaveCalendar::isRestDay($employee->company_id, $offDay, $employee));
        $this->assertFalse(LeaveCalendar::isRestDay($employee->company_id, $schedule->anchor_date->copy()->addDays(3), $employee));
    }

    public function test_acquisition_par_jours_travailles_pour_une_rotation(): void
    {
        $this->makeCompanyContext();
        $anchor = today()->subDays(28);
        $schedule = $this->rotation(14, 14, $anchor);
        $employee = $this->makeEmployee(['hire_date' => $anchor->toDateString(), 'leave_days_per_month' => 2.5]);
        Employee::whereKey($employee->id)->update(['work_schedule_id' => $schedule->id]);
        LeaveSetting::for($employee->company_id)->update(['accrual_mode' => 'per_worked_days', 'accrual_per_days' => 7]);
        $employee = $employee->fresh();

        $balances = app(LeaveBalanceService::class);
        $balances->sync($employee);
        $balances->sync($employee); // idempotent

        // 14 jours travaillés avant aujourd'hui = 2 tranches de 7 jours × 2,5 j
        $this->assertSame(5.0, $balances->balance($employee->id));
    }

    public function test_la_majoration_d_anciennete_s_ajoute_apres_quinze_ans(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee(['hire_date' => today()->subYears(16)->toDateString()]);
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee);

        // 180 premiers mois à 2,5 j ; les 12 suivants à 2,5 + 2/12 = 2,67 j (arrondi par mois)
        $this->assertEqualsWithDelta(450 + 12 * 2.67, $balances->balance($employee->id), 0.01);
    }

    public function test_sans_majoration_configuree_rien_n_est_ajoute(): void
    {
        $this->makeCompanyContext();
        $employee = $this->makeEmployee(['hire_date' => today()->subYears(16)->toDateString()]);
        LeaveSetting::for($employee->company_id)->update(['seniority_bonuses' => []]);
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee);

        $this->assertSame(480.0, $balances->balance($employee->id)); // 192 mois × 2,5 j
    }

    public function test_le_bouton_jours_feries_du_mali_ajoute_les_jours_fixes_sans_doublon(): void
    {
        $hr = $this->makeCompanyContext();

        Livewire::actingAs($hr)->test(LeaveSettingsPage::class)->call('addMaliHolidays')->call('addMaliHolidays');

        $this->assertSame(14, Holiday::count()); // 7 jours fixes × (année courante + suivante)
    }

    public function test_les_reglages_de_decompte_et_de_majoration_s_enregistrent(): void
    {
        $hr = $this->makeCompanyContext();

        Livewire::actingAs($hr)->test(LeaveSettingsPage::class)
            ->set('count_mode', 'scheduled')->set('accrual_mode', 'per_worked_days')->set('accrual_per_days', 22)
            ->set('bonuses_text', '10:1, 20:3')
            ->call('saveSettings')->assertHasNoErrors();

        $s = LeaveSetting::for($hr->company_id)->fresh();
        $this->assertSame('scheduled', $s->count_mode);
        $this->assertSame('per_worked_days', $s->accrual_mode);
        $this->assertSame(22, $s->accrual_per_days);
        $this->assertSame([[10, 1.0], [20, 3.0]], $s->seniority_bonuses);
    }

    public function test_une_rotation_sans_conge_annuel_n_acquiert_aucun_jour(): void
    {
        $this->makeCompanyContext();
        $schedule = $this->rotation(28, 14);
        $schedule->update(['grants_annual_leave' => false]);
        $employee = $this->makeEmployee(['hire_date' => today()->subMonths(6)->toDateString()]);
        Employee::whereKey($employee->id)->update(['work_schedule_id' => $schedule->id]);
        $balances = app(LeaveBalanceService::class);

        $balances->sync($employee->fresh());

        $this->assertSame(0.0, $balances->balance($employee->id));
    }

    public function test_le_nombre_de_jours_de_repos_par_an_d_une_rotation(): void
    {
        $this->makeCompanyContext();

        $this->assertSame(122, $this->rotation(28, 14)->restDaysPerYear()); // 4 semaines / 2 semaines
        $this->assertSame(122, $this->rotation(42, 21)->restDaysPerYear()); // 6 semaines / 3 semaines
        $this->assertSame(73, $this->rotation(28, 7)->restDaysPerYear());   // 4 semaines / 1 semaine
    }
}
