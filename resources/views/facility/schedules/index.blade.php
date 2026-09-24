<x-layouts.app title="Doctor schedules">
    <x-page-header title="Doctor schedules" description="The days and hours each doctor sees booked patients. Patients can only book on days when a doctor is scheduled and places remain." />

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Weekly schedule</h2></div>
            @if ($schedules->isEmpty())
                <x-empty title="No schedules yet" icon="clock">Add a schedule so that patients can book appointments.</x-empty>
            @else
                @foreach ($days as $number => $day)
                    @if ($schedules->has($number))
                        <div class="border-b border-line last:border-b-0">
                            <p class="bg-paper px-5 py-2 text-[12px] font-semibold uppercase tracking-wide text-muted">{{ $day }}</p>
                            @foreach ($schedules[$number] as $schedule)
                                <div class="flex items-center justify-between gap-3 px-5 py-3">
                                    <div>
                                        <p class="font-medium">{{ $schedule->doctor->name }}</p>
                                        <p class="text-[13px] text-muted">{{ $schedule->hours() }}, up to {{ $schedule->max_appointments }} appointments</p>
                                    </div>
                                    <form method="POST" action="{{ route('facility.schedules.destroy', $schedule) }}" onsubmit="return confirm('Remove this schedule?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="link text-sm text-red-700">Remove</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
        </section>

        <form method="POST" action="{{ route('facility.schedules.store') }}" class="panel self-start">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Add schedule</h2></div>
            @if ($doctors->isEmpty())
                <x-empty title="No doctors registered" icon="stethoscope"><a href="{{ route('facility.staff.create') }}" class="link">Register a doctor</a> first.</x-empty>
            @else
                <div class="panel-body space-y-4">
                    <x-field.select name="doctor_id" label="Doctor" :options="$doctors->pluck('name', 'id')->all()" required />
                    <x-field.select name="day_of_week" label="Day" :options="$days" required />
                    <div class="grid grid-cols-2 gap-3">
                        <x-field.input name="start_time" label="Start" type="time" value="08:00" required />
                        <x-field.input name="end_time" label="End" type="time" value="16:00" required />
                    </div>
                    <x-field.input name="max_appointments" label="Maximum appointments" type="number" min="1" max="100" value="15" required />
                </div>
                <div class="border-t border-line px-5 py-3"><button type="submit" class="btn-primary w-full">Add schedule</button></div>
            @endif
        </form>
    </div>
</x-layouts.app>
