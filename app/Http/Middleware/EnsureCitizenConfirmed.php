<?php

namespace App\Http\Middleware;

use App\Models\Citizen;
use App\Models\Credential;
use App\Services\DhpIdentityConfirmation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a recent assisted identity confirmation for the citizen in
 * context. Resolves the citizen from a Citizen model, a Credential model,
 * or a raw citizen id route parameter. Front-end forms alone are not
 * enough; this runs on every protected request.
 *
 * Usage: ->middleware('dhp.confirmed:citizen')
 */
class EnsureCitizenConfirmed
{
    public function handle(Request $request, Closure $next, string $param = 'citizen'): Response
    {
        $bound = $request->route($param);

        $citizenId = match (true) {
            $bound instanceof Citizen => $bound->id,
            $bound instanceof Credential => $bound->citizen_id,
            is_numeric($bound) => (int) $bound,
            default => null,
        };

        if ($citizenId === null || ! DhpIdentityConfirmation::confirmed($citizenId)) {
            return redirect()->route('dhp.issuer.citizens.search')
                ->with('error', 'Confirm the citizen identity before opening this page.');
        }

        return $next($request);
    }
}
