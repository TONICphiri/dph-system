<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Authenticator-app two-factor authentication (TOTP).
 * Required for patients before any medical-detail page.
 */
class TwoFactorController extends Controller
{
    /** Setup page: QR code + manual key + confirm form (or manage existing). */
    public function settings(Request $request)
    {
        $user = $request->user();

        $pendingSecret = null;
        $qrSvg = null;

        if (! $user->hasTwoFactor()) {
            $pendingSecret = session('two_factor_pending_secret');

            if (! $pendingSecret) {
                $pendingSecret = TwoFactorService::generateSecret();
                session(['two_factor_pending_secret' => $pendingSecret]);
            }

            $uri = TwoFactorService::provisioningUri(
                $user->email ?: $user->masked_nin,
                $pendingSecret,
                config('app.name', 'DHP')
            );

            try {
                $qrSvg = \App\Services\QrCodeService::generateQrCodeSvg($uri);
            } catch (\Throwable) {
                $qrSvg = null;
            }
        }

        return view('settings.2fa', compact('pendingSecret', 'qrSvg'));
    }

    /** Confirm setup with a code from the authenticator app. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $key = 'two-factor-confirm:'.$request->user()->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Reload the page and try again in a minute.']);
        }

        $secret = session('two_factor_pending_secret');
        abort_unless($secret, 400, 'No setup in progress. Reload the page.');

        if (! TwoFactorService::verify($secret, $request->input('code'))) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['code' => 'Wrong code. Check your authenticator app time and try again.']);
        }

        RateLimiter::clear($key);

        $recoveryCodes = TwoFactorService::generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => TwoFactorService::hashRecoveryCodes($recoveryCodes),
        ])->saveQuietly();

        session()->forget('two_factor_pending_secret');
        $request->session()->regenerate();
        TwoFactorService::markSessionVerified();

        AuditService::log($request->user(), 'two_factor.enabled', $request->user(), []);

        return redirect(route('settings.2fa.recovery', absolute: false))
            ->with('recovery_codes', $recoveryCodes);
    }

    /** Switch 2FA off (password required). */
    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->saveQuietly();

        TwoFactorService::clearSession();
        AuditService::log($request->user(), 'two_factor.disabled', $request->user(), []);

        return back()->with('success', 'Two-factor authentication switched off.');
    }

    /** Recovery codes, shown exactly once right after setup. */
    public function recovery()
    {
        $codes = session('recovery_codes');

        abort_unless(is_array($codes) && $codes !== [], 404);

        return view('settings.2fa-recovery', ['codes' => $codes]);
    }

    /** Challenge: enter the current 6-digit code. */
    public function challenge()
    {
        abort_unless(auth()->user()->hasTwoFactor(), 403);

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $key = 'two-factor:'.$request->user()->getKey().'|'.$request->ip();

        // 6 digits = 1M combinations: block guessing after 5 tries.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $retry = RateLimiter::availableIn($key);
            AuditService::log($request->user(), 'two_factor.locked', $request->user(), []);

            return back()->withErrors(['code' => 'Too many wrong codes. Try again in '.ceil($retry / 60).' minute(s).']);
        }

        $user = $request->user();
        abort_unless($user->hasTwoFactor(), 403);

        $code = trim($request->input('code'));

        if ($this->codeAccepted($user, $code)) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            TwoFactorService::markSessionVerified();

            return redirect()->intended(route('patient.records', absolute: false));
        }

        RateLimiter::hit($key, 900);
        AuditService::log($user, 'two_factor.failed', $user, []);

        // Uniform message: never reveal whether it was a bad code or a spent recovery code.
        return back()->withErrors(['code' => 'Invalid credentials.']);
    }

    /**
     * Accept a current TOTP code or one unused recovery code (single use).
     */
    protected function codeAccepted($user, string $code): bool
    {
        if (TwoFactorService::verify($user->two_factor_secret, $code)) {
            return true;
        }

        return TwoFactorService::consumeRecoveryCode($user, $code);
    }
}
