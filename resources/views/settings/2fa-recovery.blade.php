<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Security</p>
            <h2 class="text-2xl font-extrabold leading-tight">Save your recovery codes</h2>
            <p class="text-sm text-dhp-100">Shown once. Each code works a single time if you lose your phone.</p>
        </div>
    </x-slot>

    <div class="dhp-card dhp-card-pad max-w-xl">
        <div class="dhp-alert dhp-alert-warning" role="alert">
            <p class="text-sm"><strong>Write these down now.</strong> This page will not show them again. Without your phone and without these codes, only your facility's identity desk can restore access.</p>
        </div>
        <ol class="mt-4 grid gap-2 sm:grid-cols-2">
            @foreach ($codes as $code)
                <li class="dhp-mono border border-[#DCE8E8] bg-dhp-50 px-3 py-2">{{ $code }}</li>
            @endforeach
        </ol>
        <a href="{{ route('patient.records') }}" class="btn-primary mt-5">Saved — continue to My Records</a>
    </div>
</x-app-layout>
