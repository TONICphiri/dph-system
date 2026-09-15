<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the currently authenticated user.
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * Patients viewing their OWN linked file must pass 2FA first.
     * Returns a redirect to the challenge, or null when verified / not applicable.
     */
    protected function patientFileTwoFactorRedirect(\App\Models\Patient $patient)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('patient') && $user->ownsPatient($patient)
            && ! \App\Services\TwoFactorService::sessionVerified()) {
            if (request()->expectsJson() || request()->wantsJson()) {
                abort(403, 'Two-factor verification required.');
            }

            return redirect()->route('two-factor.challenge');
        }

        return null;
    }
}
