<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\LeaveBalanceService;
use Illuminate\Console\Command;

class AccrueLeave extends Command
{
    protected $signature = 'leaves:accrue';

    protected $description = 'Crédite les jours de congé acquis (mois de présence révolus) et fait expirer les jours de récupération périmés';

    public function handle(LeaveBalanceService $balances): int
    {
        $count = 0;

        Employee::withoutGlobalScopes()->where('status', 'active')->whereNull('deleted_at')
            ->chunkById(200, function ($employees) use ($balances, &$count) {
                foreach ($employees as $employee) {
                    $balances->sync($employee);
                    $count++;
                }
            });

        $this->info("{$count} employé(s) mis à jour.");

        return self::SUCCESS;
    }
}
