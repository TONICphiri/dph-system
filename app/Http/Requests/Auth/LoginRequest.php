<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\NinLookupService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * NIN-based authentication per system-description2.md FR-A4, FR-A7, §5.4.
 * - Login identifier is the National Identity Number (NIN + password).
 * - Legacy email fallback is kept for existing staff/test accounts.
 * - Uniform "invalid credentials" message: never reveals whether the NIN exists.
 * - 5 failed attempts → 15 min lock + notify (FR-A7).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nin' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'email' => ['required_without:nin', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $password = (string) $this->input('password');
        $user = $this->resolveUser();

        // Uniform response: wrong NIN and wrong password look identical (FR-A4).
        // Error key matches the submitted identifier so email-based logins
        // (legacy staff + existing tests) see errors under `email`.
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey(), 900);

            if ($user) {
                $this->registerFailedAttempt($user);
            }

            throw ValidationException::withMessages([
                $this->errorKey() => 'Invalid credentials.',
            ]);
        }

        // Correct password but account locked?
        if ($user->locked_until && $user->locked_until->isFuture()) {
            throw ValidationException::withMessages([
                $this->errorKey() => 'Account locked. Try again in '.$user->locked_until->diffForHumans(null, true).'.',
            ]);
        }

        // Catalogue lifecycle states (§5.2). Correct-password holders get a
        // specific message; unknown-NIN vs wrong-password stays uniform above.
        if (in_array($user->status, ['suspended', 'deactivated', 'inactive'], true)) {
            throw ValidationException::withMessages([
                $this->errorKey() => 'This account is inactive. Please visit a registered facility.',
            ]);
        }

        if ($user->status === 'pending') {
            throw ValidationException::withMessages([
                $this->errorKey() => 'Account awaiting facility approval. Please contact your facility.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        // Reset lockout counters on success (FR-A7).
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->saveQuietly();

        RateLimiter::clear($this->throttleKey());
    }

    protected function resolveUser(): ?User
    {
        if ($nin = $this->input('nin')) {
            return NinLookupService::findByNin((string) $nin);
        }

        if ($email = $this->input('email')) {
            // Legacy fallback: allow email for pre-catalogue staff accounts.
            // If the value looks like a NIN, try the catalogue first.
            $asNin = NinLookupService::findByNin((string) $email);
            if ($asNin) {
                return $asNin;
            }

            return User::where('email', $email)->first();
        }

        return null;
    }

    protected function registerFailedAttempt(User $user): void
    {
        $attempts = ((int) $user->failed_login_attempts) + 1;

        $patch = ['failed_login_attempts' => $attempts];

        if ($attempts >= 5) {
            $patch['locked_until'] = now()->addMinutes(15);
            $patch['failed_login_attempts'] = 0;
        }

        $user->forceFill($patch)->saveQuietly();
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->errorKey() => 'Too many attempts. Try again in '.ceil($seconds / 60).' minutes.',
        ]);
    }

    /**
     * Error bag key matching the submitted identifier: `nin` for NIN
     * logins, `email` for legacy email logins.
     */
    protected function errorKey(): string
    {
        return $this->input('nin') ? 'nin' : 'email';
    }

    public function throttleKey(): string
    {
        $login = (string) ($this->input('nin') ?? $this->input('email') ?? '');

        return Str::transliterate(Str::lower($login).'|'.$this->ip());
    }
}
