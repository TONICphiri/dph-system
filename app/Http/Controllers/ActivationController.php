<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * First-login activation per system-description2.md FR-A5, §6.3 (/activate/*).
 * Sets a personal password (strength meter in view) and clears the forced flag.
 */
class ActivationController extends Controller
{
    public function showPassword()
    {
        return view('activate.password');
    }

    public function storePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->saveQuietly();

        AuditService::log($user, 'activation.completed', $user, ['facility_id' => $user->facility_id]);

        return redirect()->route('dashboard')->with('success', 'Password set. Welcome to your Digital Health Passport.');
    }

    /** Signed, single-use, expiring activation link entry (SMS/email one-time setup link). */
    public function showSigned(Request $request, \App\Models\User $user)
    {
        abort_unless($request->hasValidSignature(), 403);

        return view('activate.signed', ['user' => $user]);
    }
}
