@php $people = collect([$patient])->merge($patient->children); @endphp
<x-layouts.app title="Book an appointment">
    <x-page-header title="Book an appointment" description="Choose a facility and a date to see which doctors are available. The facility reviews every request and you are notified of the decision.">
        <x-slot:breadcrumb><a href="{{ route('portal.appointments.index') }}" class="hover:text-brand-700">My appointments</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Book</x-slot:breadcrumb>
    </x-page-header>

    {{-- Step 1: facility and date --}}
    <form method="GET" action="{{ route('portal.appointments.create') }}" class="panel mb-6">
        <div class="panel-header"><h2 class="panel-title">1. Facility and date</h2></div>
        <div class="panel-body grid gap-5 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
            <x-field.select name="facility_id" label="Health facility" required :value="$facility?->id"
                :options="$facilities->mapWithKeys(fn ($item) => [$item->id => $item->name.', '.$item->district->name])->all()" />
            <x-field.input name="date" label="Date" type="date" :min="today()->toDateString()" :value="$date?->toDateString()" required />
            <button type="submit" class="btn-secondary">Check availability</button>
        </div>
    </form>

    @if ($doctors !== null)
        @if ($doctors->isEmpty())
            <section class="panel">
                <x-empty title="No doctor is available on this date" icon="calendar">{{ $facility->name }} has no free places on {{ $date->format('l j F Y') }}. Please choose another date or facility.</x-empty>
            </section>
        @else
            {{-- Step 2: doctor, patient and reason --}}
            <form method="POST" action="{{ route('portal.appointments.store') }}" class="panel">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">
                <div class="panel-header"><h2 class="panel-title">2. Doctor and reason</h2><span class="text-[13px] text-muted">{{ $facility->name }}, {{ $date->format('l j F Y') }}</span></div>
                <div class="panel-body space-y-5">
                    <fieldset>
                        <legend class="label">Doctor</legend>
                        <div class="grid gap-2 md:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 border border-line p-3 has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50">
                                <input type="radio" name="doctor_id" value="" class="mt-1 text-brand-700 focus:ring-brand-600" @checked(! old('doctor_id'))>
                                <span><span class="block font-medium">Any available doctor</span><span class="text-[13px] text-muted">The doctor with the most free places is chosen</span></span>
                            </label>
                            @foreach ($doctors as $slot)
                                <label class="flex cursor-pointer items-start gap-3 border border-line p-3 has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="doctor_id" value="{{ $slot['doctor']->id }}" class="mt-1 text-brand-700 focus:ring-brand-600" @checked(old('doctor_id') == $slot['doctor']->id)>
                                    <span>
                                        <span class="block font-medium">{{ $slot['doctor']->name }}</span>
                                        <span class="block text-[13px] text-muted">{{ $slot['doctor']->job_title }}{{ $slot['doctor']->job_title ? ', ' : '' }}{{ $slot['schedule']->hours() }}</span>
                                        <span class="block text-[13px] text-muted">{{ $slot['remaining'] }} {{ \Illuminate\Support\Str::plural('place', $slot['remaining']) }} left{{ $slot['rating'] ? '. Rated '.$slot['rating'].' of 5 by patients' : '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('doctor_id')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field.select name="patient_id" label="Appointment for" required :placeholder="false" :value="$patient->id"
                            :options="$people->mapWithKeys(fn ($person) => [$person->id => $person->id === $patient->id ? 'Myself' : $person->full_name.' (child)'])->all()" />
                        <x-field.input name="reason" label="Reason for the appointment" required placeholder="For example, follow up of blood pressure" />
                    </div>
                </div>
                <div class="flex justify-end border-t border-line px-5 py-3"><button type="submit" class="btn-primary">Send request</button></div>
            </form>
        @endif
    @endif
</x-layouts.app>
