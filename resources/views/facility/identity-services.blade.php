<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Facility · Identity services desk</p>
            <h2 class="text-2xl font-extrabold leading-tight">Password reset &amp; credential recovery</h2>
            <p class="text-sm text-dhp-100">Person must be physically present and verified. Every action is logged with your staff identity.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('facility.identity-services.reset') }}" class="dhp-card dhp-card-pad max-w-xl">
        @csrf
        <label class="dhp-label" for="nin">Holder National ID Number</label>
        <input id="nin" name="nin" required class="dhp-input" placeholder="Enter NIN of the person present" />
        @error('nin')<p class="dhp-field-error">{{ $message }}</p>@enderror
        <div class="mt-4">
            <label class="dhp-label" for="staff_pin_confirm">Re-enter YOUR password to confirm physical verification</label>
            <input id="staff_pin_confirm" type="password" name="staff_pin_confirm" required class="dhp-input" autocomplete="off" />
            @error('staff_pin_confirm')<p class="dhp-field-error">{{ $message }}</p>@enderror
        </div>
        <button class="btn-primary mt-5">Verify &amp; log recovery</button>
    </form>
</x-app-layout>
