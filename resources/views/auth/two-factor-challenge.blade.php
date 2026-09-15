<x-guest-layout>
    <div class="mx-auto max-w-md rounded-none border border-[#DCE8E8] bg-white p-6 shadow-card">
        <p class="text-xs font-bold uppercase tracking-widest text-dhp-600">Two-factor check</p>
        <h1 class="mt-1 text-2xl font-extrabold text-dhp-900">Enter your code</h1>
        <p class="mt-1 text-sm text-slate-500">Open your authenticator app and type the 6-digit code for this account. Lost your phone? Type one of your recovery codes instead.</p>

        <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-4 grid gap-3">
            @csrf
            <div>
                <label class="dhp-label" for="code">6-digit code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required maxlength="6" autofocus class="dhp-input" placeholder="123456" />
                @error('code')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary">Verify</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="text-sm font-bold text-slate-500 hover:underline">Cancel and sign out</button>
        </form>
    </div>
</x-guest-layout>
