<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * FR-A5: users with must_change_password may only visit activation /
 * password / logout routes until they set a personal password.
 */
class MustChangePassword
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowed = [
                'activate.password',
                'activate.password.store',
                'logout',
                'verification.notice',
            ];

            if (! in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route('activate.password');
            }
        }

        return $next($request);
    }
}
