<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Minimal audit writer per system-description2.md §5.2, §7.7.
 * Every enrollment / approval / verification transition is recorded.
 */
class AuditService
{
    public static function log(?User $actor, string $action, $subject = null, array $extra = []): void
    {
        try {
            DB::table('audit_logs')->insert([
                'action' => $action,
                'subject_type' => $subject ? get_class($subject) : 'system',
                'subject_id' => $subject?->getKey(),
                'user_id' => $actor?->id ?? $subject?->getKey() ?? 1,
                'ip_address' => request()->ip(),
                'description' => trim($action.' '.json_encode([
                    'facility_id' => $extra['facility_id'] ?? $actor?->facility_id,
                    'reason' => $extra['reason'] ?? null,
                ])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit must never break the primary flow; log locally instead.
            try {
                \Log::warning('audit_log_failed', ['action' => $action, 'error' => $e->getMessage()]);
            } catch (\Throwable $ignored) {
            }
        }
    }
}
