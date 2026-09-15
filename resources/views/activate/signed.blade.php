<x-guest-layout>
    <div class="mx-auto max-w-md rounded-2xl border border-[#DCE8E8] bg-white p-6 text-center shadow-card">
        <p class="text-xs font-bold uppercase tracking-widest text-dhp-600">Activation link</p>
        <h1 class="mt-1 text-2xl font-extrabold text-dhp-900">Hello, {{ $user->display_name }}</h1>
        <p class="mt-2 text-sm text-slate-500">Your account ({{ $user->masked_nin }}) was created at a registered facility. Please sign in with your NIN to set your personal password.</p>
        <a href="{{ route('login') }}" class="btn-primary mt-4">Continue to NIN sign-in</a>
    </div>
</x-guest-layout>
