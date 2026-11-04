<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use Throwable;

/** Trace de toutes les actions de l'équipe plateforme : qui, quoi, sur quelle entreprise, quand, depuis où. */
class PlatformAudit
{
    public static function log(string $action, string $summary, ?int $companyId = null, array $meta = []): void
    {
        try {
            PlatformAuditLog::create([
                'user_id' => auth()->id(), 'company_id' => $companyId, 'action' => $action,
                'summary' => mb_substr($summary, 0, 255), 'meta' => $meta ?: null,
                'ip' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 500), 'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e); // un échec de journalisation ne doit jamais bloquer l'action
        }
    }
}
