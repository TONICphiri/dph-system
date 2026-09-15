<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // FR-A5: first-login flag forces a personal password set before any other page.
        if ($user->must_change_password) {
            return redirect()->route('activate.password');
        }

        // Role-aware landing (§6.1): each role lands on its own dashboard.
        // Super/System admins → national admin home; verifiers → verify portal;
        // patients → passport dashboard; everyone else → facility dashboard.
        if ($user->isNationalAdmin() || $user->hasAnyRole(['super_admin', 'system_admin', 'admin', 'national_admin'])) {
            $home = route('admin.dashboard', absolute: false);
        } elseif ($user->hasRole('verifier')) {
            $home = route('verify.scan', absolute: false);
        } elseif ($user->hasRole('patient')) {
            $home = route('patient.credential', absolute: false);
        } else {
            $home = route('dashboard', absolute: false);
        }

        return redirect()->intended($home);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
