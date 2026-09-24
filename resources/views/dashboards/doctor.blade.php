<x-layouts.app title="Dashboard">
    <x-page-header title="Consultations" description="Patients waiting to be seen, your inpatients and your upcoming appointments.">
        <x-slot:actions>
            <a href="{{ route('visits.queue') }}" class="btn-primary"><x-icon name="list" class="h-4 w-4" /> Patient queue</a>
            <a href="{{ route('patients.index') }}" class="btn-secondary"><x-icon name="search" class="h-4 w-4" /> Find patient</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Waiting for a doctor" :value="$stats['waitingForDoctor']" icon="clock" :href="route('visits.queue')" :tone="$stats['waitingForDoctor'] > 10 ? 'warning' : 'default'" />
        <x-stat label="Seen by you today" :value="$stats['seenToday']" icon="stethoscope" />
        <x-stat label="Your inpatients" :value="$stats['myInpatients']" icon="bed" :href="route('admissions.index')" />
        <x-stat label="Your appointments today" :value="$stats['myAppointmentsToday']" icon="calendar" :href="route('appointments.index')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="panel xl:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Consultation queue</h2></div>
            @if ($consultationQueue->isEmpty())
                <x-empty title="No patients are waiting for a doctor" icon="stethoscope" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Patient</th><th>Reason for visit</th><th>Vital signs</th><th>Waiting</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($consultationQueue as $visit)
                                @php $vital = $visit->vitals->last(); @endphp
                                <tr>
                                    <td><x-patient-cell :patient="$visit->patient" /></td>
                                    <td>{{ $visit->reason_for_visit }}</td>
                                    <td class="text-[13px] text-muted">
                                        @if ($vital)
                                            {{ $vital->temperature ? $vital->temperature.' °C' : '' }}{{ $vital->bloodPressure() ? ', '.$vital->bloodPressure() : '' }}{{ $vital->weight ? ', '.$vital->weight.' kg' : '' }}
                                        @else
                                            Not recorded
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-muted">{{ $visit->checked_in_at->diffForHumans(null, true) }}</td>
                                    <td class="text-right"><a href="{{ route('consultations.create', $visit) }}" class="btn-primary btn-sm">Start</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Upcoming appointments</h2></div>
            @forelse ($appointments as $appointment)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <div>
                        <p class="font-medium">{{ $appointment->patient->full_name }}</p>
                        <p class="text-[13px] text-muted">{{ $appointment->appointment_date->format('D j M') }}. {{ $appointment->reason }}</p>
                    </div>
                    <x-status :value="$appointment->status" />
                </div>
            @empty
                <x-empty title="No upcoming appointments" icon="calendar" />
            @endforelse
        </section>
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Inpatients at this facility</h2><a href="{{ route('admissions.index') }}" class="link text-sm">All admissions</a></div>
        @include('partials.inpatient-list', ['admissions' => $inpatients])
    </section>
</x-layouts.app>
