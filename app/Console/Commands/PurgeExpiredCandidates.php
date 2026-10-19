<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredCandidates extends Command
{
    protected $signature = 'recruitment:purge-expired';

    protected $description = 'Supprime les profils de la banque de CV dont la durée de conservation est dépassée';

    public function handle(): int
    {
        $count = 0;

        // Jamais de profil embauché ni de profil dont une candidature est encore en cours.
        $inProgress = Application::withoutGlobalScopes()
            ->whereNotIn('stage', ['rejected'])->select('candidate_id');

        Candidate::withoutGlobalScopes()
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<', today())
            ->where('status', '!=', 'hired')
            ->whereNotIn('id', $inProgress)
            ->chunkById(100, function ($chunk) use (&$count) {
                foreach ($chunk as $candidate) {
                    if ($candidate->cv_path) {
                        Storage::disk('local')->delete($candidate->cv_path);
                    }

                    $candidate->delete();
                    $count++;
                }
            });

        $this->info("{$count} profil(s) supprimé(s).");

        return self::SUCCESS;
    }
}
