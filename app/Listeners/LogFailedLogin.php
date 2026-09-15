<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;

class LogFailedLogin
{
    /**
     * Records failed login attempts and lockouts to the audit log so
     * repeated attempts against a patient-data system are visible to admins,
     * not just silently rate-limited.
     */
    public function handleFailed(Failed $event): void
    {
        AuditLog::create([
            'action' => 'login_failed',
            'subject_type' => \App\Models\User::class,
            'subject_id' => $event->user?->id ?? 0,
            'user_id' => $event->user?->id,
            'ip_address' => request()->ip(),
            'description' => 'Failed login attempt for '.($event->credentials['email'] ?? 'unknown email').' from '.request()->ip(),
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        AuditLog::create([
            'action' => 'login_lockout',
            'subject_type' => \App\Models\User::class,
            'subject_id' => 0,
            'user_id' => null,
            'ip_address' => $event->request->ip(),
            'description' => 'Login rate-limited (too many attempts) for '.$event->request->input('email').' from '.$event->request->ip(),
        ]);
    }
}
