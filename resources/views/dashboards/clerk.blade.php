<x-layouts.app title="Dashboard">
    <x-page-header title="Registration desk" description="Find returning patients by National ID, passport number or QR card, and register new patients.">
        <x-slot:actions>
            <a href="{{ route('patients.scan') }}" class="btn-secondary"><x-icon name="qr" class="h-4 w-4" /> Scan card</a>
            <a href="{{ route('patients.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register patient</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('patients.index') }}" class="panel mb-6 flex flex-col gap-3 p-4 sm:flex-row">
        <label for="search" class="sr-only">Search patients</label>
        <input id="search" name="search" class="input flex-1" placeholder="National ID, passport number, name or phone number" autofocus>
        <button type="submit" class="btn-primary"><x-icon name="search" class="h-4 w-4" /> Find patient</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Registered today" :value="$stats['registeredToday']" icon="user-plus" />
        <x-stat label="Checked in today" :value="$stats['checkedInToday']" icon="clipboard" />
        <x-stat label="Waiting to be seen" :value="$stats['waiting']" icon="clock" :href="route('visits.queue')" />
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Recently registered at this facility</h2></div>
        @if ($recentPatients->isEmpty())
            <x-empty title="No patients registered yet" icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Patient</th><th>National ID</th><th>Sex</th><th>Age</th><th>Registered</th></tr></thead>
                    <tbody>
                        @foreach ($recentPatients as $patient)
                            <tr>
                                <td><x-patient-cell :patient="$patient" /></td>
                                <td class="mono">{{ $patient->national_id ?? 'Child record' }}</td>
                                <td>{{ $patient->sex->label() }}</td>
                                <td>{{ $patient->age_label }}</td>
                                <td class="text-muted">{{ $patient->created_at->format('j M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
