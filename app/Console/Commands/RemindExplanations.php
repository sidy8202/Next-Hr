<?php

namespace App\Console\Commands;

use App\Services\ExplanationService;
use Illuminate\Console\Command;

class RemindExplanations extends Command
{
    protected $signature = 'explanations:remind';

    protected $description = 'Rappelle aux employés les réponses attendues demain et prévient la RH des délais dépassés';

    public function handle(ExplanationService $service): int
    {
        $r = $service->remind();
        $this->info("{$r['reminded']} rappel(s), {$r['overdue']} délai(s) dépassé(s) signalé(s).");

        return self::SUCCESS;
    }
}
