<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class SyncSubscriptions extends Command
{
    protected $signature = 'platform:sync-subscriptions';

    protected $description = 'Met à jour les statuts des abonnements (retards, expirations) et envoie les rappels d’échéance.';

    public function handle(SubscriptionService $service): int
    {
        $r = $service->syncStatuses();
        $this->info("Retards : {$r['past_due']} · Expirés : {$r['expired']} · Rappels : {$r['notices']} · E-mails envoyés : {$r['emails']}");

        return self::SUCCESS;
    }
}
