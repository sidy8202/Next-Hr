<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class BirthdayCommandTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_la_notification_est_envoyee_une_seule_fois_par_an(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $employee = $this->makeEmployee($dept, $this->makePosition($dept));
        $employee->forceFill(['birth_date' => now($company->timezone)->subYears(30)->toDateString()])->save();

        $user = $this->makeUser($company, employee: $employee);

        $this->artisan('app:send-birthday-notifications', ['--force-hour' => true])->assertSuccessful();
        $this->artisan('app:send-birthday-notifications', ['--force-hour' => true])->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertStringContainsString('Awa', $user->notifications()->first()->data['message']);
    }

    public function test_pas_de_notification_un_autre_jour(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $employee = $this->makeEmployee($dept, $this->makePosition($dept));
        $employee->forceFill(['birth_date' => now($company->timezone)->subYears(30)->addDays(5)->toDateString()])->save();

        $user = $this->makeUser($company, employee: $employee);

        $this->artisan('app:send-birthday-notifications', ['--force-hour' => true])->assertSuccessful();

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_les_employes_sans_compte_sont_ignores_sans_erreur(): void
    {
        $company = $this->makeCompany();
        $dept = $this->makeDepartment($company);
        $employee = $this->makeEmployee($dept, $this->makePosition($dept));
        $employee->forceFill(['birth_date' => now($company->timezone)->subYears(40)->toDateString()])->save();

        $this->artisan('app:send-birthday-notifications', ['--force-hour' => true])->assertSuccessful();

        $this->assertSame(1, Employee::withoutGlobalScopes()->count());
    }
}
