<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;

/**
 * Patients must complete two-factor authentication before touching
 * any of their medical-detail pages. First visit forces authenticator
 * setup; later visits require a fresh code when the session lapses.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('patient')) {
            return $next($request);
        }

        if (! $user->hasTwoFactor()) {
            return redirect()->route('settings.2fa')
                ->with('warning', 'Set up two-factor authentication to view your medical details.');
        }

        if (! TwoFactorService::sessionVerified()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                abort(403, 'Two-factor verification required.');
            }

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
