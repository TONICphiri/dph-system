<?php

namespace App\Http\Middleware;

use App\Enums\DhpRole;
use App\Services\DhpAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Digital Health Passport role gate. Uses users.role + users.is_active only;
 * legacy Spatie roles are ignored for DHP routes.
 *
 * Usage: ->middleware('dhp.role:issuer') or ('dhp.role:issuer,admin').
 */
class EnsureDhpRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            DhpAuditLogger::log(
                user: $user,
                action: 'inactive_account_blocked',
                entityType: 'user',
                entityId: $user->id,
                details: ['route' => $request->route()?->getName()],
                ipAddress: $request->ip(),
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        $allowed = collect($roles)->map(fn (string $role) => DhpRole::tryFrom($role))->filter()->all();

        if (! in_array($user->role, $allowed, true)) {
            DhpAuditLogger::log(
                user: $user,
                action: 'unauthorized_access_attempt',
                entityType: 'route',
                entityId: null,
                details: ['route' => $request->route()?->getName(), 'reason' => 'role_denied'],
                ipAddress: $request->ip(),
            );

            abort(403, 'You do not have permission to open this page.');
        }

        return $next($request);
    }
}
