<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\LeaveSetting;
use App\Support\LeaveCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Feature\Concerns\RecruitmentFixtures;
use Tests\TestCase;

class LeaveCalendarTest extends TestCase
{
    use RefreshDatabase, RecruitmentFixtures;

    public function test_les_week_ends_ne_comptent_pas(): void
    {
        $user = $this->makeUser();
        LeaveSetting::for($user->company_id)->update(['count_mode' => 'scheduled']);
        $monday = today()->next(Carbon::MONDAY);

        $this->assertSame(5.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(4)));
        $this->assertSame(5.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(6))); // + samedi, dimanche
        $this->assertSame(6.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(8))); // + lundi suivant
    }

    public function test_un_jour_ferie_ne_compte_pas(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        LeaveSetting::for($user->company_id)->update(['count_mode' => 'scheduled']);
        $monday = today()->next(Carbon::MONDAY);
        Holiday::create(['date' => $monday->copy()->addDays(2)->toDateString(), 'name' => 'Fête']);

        $this->assertSame(4.0, LeaveCalendar::countDays($user->company_id, $monday, $monday->copy()->addDays(4)));
    }

    public function test_les_demi_journees(): void
    {
        $user = $this->makeUser();
        $monday = today()->next(Carbon::MONDAY);
        $friday = $monday->copy()->addDays(4);

        $this->assertSame(4.5, LeaveCalendar::countDays($user->company_id, $monday, $friday, true, false));  // commence l'après-midi
        $this->assertSame(4.0, LeaveCalendar::countDays($user->company_id, $monday, $friday, true, true));   // + termine à midi
        $this->assertSame(0.5, LeaveCalendar::countDays($user->company_id, $monday, $monday, true, false));  // une demi-journée
    }

    public function test_matin_et_apres_midi_le_meme_jour_est_refuse(): void
    {
        $user = $this->makeUser();
        $monday = today()->next(Carbon::MONDAY);

        $this->expectException(InvalidArgumentException::class);
        LeaveCalendar::countDays($user->company_id, $monday, $monday, true, true);
    }

    public function test_la_reprise_est_le_prochain_jour_ouvre(): void
    {
        $user = $this->makeUser();
        $friday = today()->next(Carbon::MONDAY)->addDays(4);

        $this->assertSame('Monday', LeaveCalendar::nextWorkday($user->company_id, $friday)->englishDayOfWeek);
    }

    public function test_dimanche_est_un_jour_de_repos_et_mercredi_non(): void
    {
        $user = $this->makeUser();

        $this->assertTrue(LeaveCalendar::isRestDay($user->company_id, today()->previous(Carbon::SUNDAY)));
        $this->assertFalse(LeaveCalendar::isRestDay($user->company_id, today()->previous(Carbon::WEDNESDAY)));
    }
}
