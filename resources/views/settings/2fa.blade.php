<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Security</p>
            <h2 class="text-2xl font-extrabold leading-tight">Two-factor authentication</h2>
            <p class="text-sm text-dhp-100">Required for patients viewing medical details. Uses any authenticator app.</p>
        </div>
    </x-slot>

    <div class="dhp-card dhp-card-pad max-w-xl">
        @if (Auth::user()->hasTwoFactor())
            <h3 class="dhp-section-title">Protection is on</h3>
            <p class="mt-2 text-sm text-slate-600">Your account asks for a 6-digit code from your authenticator app after password sign-in. Codes stay valid for 12 hours per device session.</p>
            <p class="mt-1 text-sm text-slate-600">Recovery codes left: <strong>{{ \App\Services\TwoFactorService::remainingRecoveryCodes(Auth::user()) }} of 10</strong>. Each works once if you lose your phone.</p>
            <form method="POST" action="{{ route('settings.2fa.disable') }}" class="mt-4 grid gap-3">
                @csrf
                <div>
                    <label class="dhp-label" for="password">Confirm with your password to switch off</label>
                    <input id="password" type="password" name="password" required class="dhp-input" autocomplete="current-password" />
                    @error('password')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <div><button class="btn-danger">Switch off 2FA</button></div>
            </form>
        @else
            <h3 class="dhp-section-title">Step 1 — scan with your authenticator app</h3>
            <p class="mt-2 text-sm text-slate-600">Open Google Authenticator, Microsoft Authenticator, or any TOTP app and scan the code. Then enter the 6-digit code below.</p>
            @if ($qrSvg)
                <div class="mt-3 border border-[#DCE8E8] bg-white p-3" style="max-width:260px">{!! $qrSvg !!}</div>
            @endif
            <p class="mt-3 text-sm">Manual key: <code class="dhp-mono">{{ $pendingSecret }}</code></p>

            <h3 class="dhp-section-title mt-6">Step 2 — confirm the code</h3>
            <form method="POST" action="{{ route('settings.2fa.confirm') }}" class="mt-3 grid max-w-xs gap-3">
                @csrf
                <div>
                    <label class="dhp-label" for="code">6-digit code</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="6" class="dhp-input" placeholder="123456" />
                    @error('code')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <div><button class="btn-primary">Confirm and switch on</button></div>
            </form>
        @endif
    </div>
</x-app-layout>
