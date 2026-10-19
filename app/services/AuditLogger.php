<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Journal d'audit : qui a fait quoi, sur quelle table, avec quel avant / après. */
class AuditLogger
{
    public static function log(string $action, string $table, ?int $recordId = null, ?array $before = null, ?array $after = null, ?int $companyId = null): void
    {
        $user = auth()->user();

        DB::table('audit_logs')->insert([
            'company_id' => $companyId ?? $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'changes' => json_encode(['before' => $before, 'after' => $after], JSON_UNESCAPED_UNICODE),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
