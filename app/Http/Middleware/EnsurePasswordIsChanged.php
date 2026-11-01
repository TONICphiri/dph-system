<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Users signing in with a one time password must choose their own password
 * before they can use the system.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // The dashboard renders the blocking "change password" pop-up, so it is
        // always allowed. Every other page sends the user back there until the
        // one time password has been replaced.
        if ($user && $user->must_change_password && ! $request->routeIs('dashboard', 'password.change', 'password.change.update', 'logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Change your password to continue.'], 423);
            }

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
