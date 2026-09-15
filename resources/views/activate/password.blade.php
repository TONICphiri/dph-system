<x-guest-layout>
    <div class="mx-auto max-w-md rounded-2xl border border-[#DCE8E8] bg-white p-6 shadow-card">
        <p class="text-xs font-bold uppercase tracking-widest text-dhp-600">First-login activation</p>
        <h1 class="mt-1 text-2xl font-extrabold text-dhp-900">Set your personal password</h1>
        <p class="mt-1 text-sm text-slate-500">Your facility created this account. Set a password before continuing. Minimum 8 characters with mixed case and numbers.</p>

        <form method="POST" action="{{ route('activate.password.store') }}" class="mt-4 grid gap-3">
            @csrf
            <div>
                <label class="dhp-label" for="password">New password</label>
                <input id="password" type="password" name="password" required class="dhp-input" autocomplete="new-password" />
                <div class="mt-2 h-2 rounded bg-slate-100"><div id="pw-meter" class="h-2 rounded bg-dhp-600" style="width:0%"></div></div>
                @error('password')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dhp-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="dhp-input" autocomplete="new-password" />
            </div>
            <button class="btn-primary">Set password &amp; continue</button>
        </form>
    </div>
    <script>
    (function () {
        var p = document.getElementById('password'), m = document.getElementById('pw-meter');
        if (!p) return;
        p.addEventListener('input', function () {
            var v = p.value, score = 0;
            if (v.length >= 8) score += 25;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score += 25;
            if (/\d/.test(v)) score += 25;
            if (/[^A-Za-z0-9]/.test(v)) score += 25;
            m.style.width = score + '%';
        });
    })();
    </script>
</x-guest-layout>
