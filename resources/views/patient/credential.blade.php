<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Patient · Digital passport</p>
            <h2 class="text-2xl font-extrabold leading-tight">My health credential</h2>
            <p class="text-sm text-dhp-100">{{ Auth::user()->masked_nin }} · Share, print, or revoke your QR.</p>
        </div>
    </x-slot>

    <section class="dhp-card dhp-card-pad mb-6 max-w-xl" style="border-top:5px solid #0E7490">
        <p class="dhp-eyebrow">Digital Health Passport</p>
        <h3 class="dhp-section-title">{{ Auth::user()->display_name }}</h3>
        <p class="text-sm text-slate-500">{{ Auth::user()->masked_nin }}</p>
        @if ($credentials->first())
            <div class="mt-3 rounded-xl bg-slate-900 p-4 font-mono text-xs text-cyan-100" style="word-break:break-all">{{ substr($credentials->first()->payload_signed, 0, 220) }}…</div>
            <p class="mt-2 text-xs text-slate-500">Type: {{ $credentials->first()->credential_type }} · Expires: {{ $credentials->first()->expires_at }} · Status: {{ ucfirst($credentials->first()->status) }}</p>
        @else
            <p class="mt-3 text-sm text-slate-500">No active credential yet. Issue your first QR below.</p>
        @endif
        <div class="mt-4 flex gap-2">
            <form method="POST" action="{{ route('patient.credential.issue') }}">@csrf<button class="btn-primary">Issue new QR</button></form>
            @if ($credentials->first() && $credentials->first()->status === 'active')
                <form method="POST" action="{{ route('patient.credential.revoke', $credentials->first()->id) }}">@csrf<button class="btn-danger">Revoke</button></form>
            @endif
        </div>
    </section>

    <section class="dhp-card dhp-card-pad max-w-xl">
        <h3 class="dhp-section-title">Recent credentials</h3>
        <ul class="mt-2 divide-y text-sm">
            @forelse ($credentials as $c)
                <li class="py-2 flex justify-between gap-3">
                    <span class="dhp-mono">#{{ $c->id }} · {{ $c->status }}</span>
                    <span class="text-xs text-slate-500">{{ $c->expires_at }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-500">None yet.</li>
            @endforelse
        </ul>
        <p class="mt-3 text-xs text-slate-500"><a class="font-bold text-dhp-700" href="{{ route('patient.appointments') }}">My appointments →</a> · <a class="font-bold text-dhp-700" href="{{ route('patient.consents') }}">Sharing consents →</a></p>
    </section>
</x-app-layout>
