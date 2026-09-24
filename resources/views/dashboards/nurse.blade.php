<x-layouts.app title="Dashboard">
    <x-page-header title="Nursing station" description="Patients waiting for vital signs and patients on the wards.">
        <x-slot:actions>
            <a href="{{ route('visits.queue') }}" class="btn-primary"><x-icon name="list" class="h-4 w-4" /> Patient queue</a>
            <a href="{{ route('facility.bed-board') }}" class="btn-secondary"><x-icon name="bed" class="h-4 w-4" /> Bed board</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Waiting for vital signs" :value="$stats['waitingForVitals']" icon="activity" :href="route('visits.queue')" :tone="$stats['waitingForVitals'] > 5 ? 'warning' : 'default'" />
        <x-stat label="Patients on the wards" :value="$stats['inpatients']" icon="bed" :href="route('admissions.index')" />
        <x-stat label="Waiting for a bed" :value="$stats['awaitingBed']" icon="clock" :tone="$stats['awaitingBed'] > 0 ? 'warning' : 'default'" :href="route('admissions.index')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Vital signs queue</h2></div>
            @forelse ($vitalsQueue as $visit)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <div>
                        <x-patient-cell :patient="$visit->patient" />
                        <p class="text-[13px] text-muted">{{ $visit->reason_for_visit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[13px] text-muted">Waiting {{ $visit->checked_in_at->diffForHumans(null, true) }}</p>
                        <a href="{{ route('vitals.create', $visit) }}" class="btn-primary btn-sm mt-1">Record vitals</a>
                    </div>
                </div>
            @empty
                <x-empty title="No patients are waiting for vital signs" icon="activity" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Inpatients</h2><a href="{{ route('admissions.index') }}" class="link text-sm">All admissions</a></div>
            @include('partials.inpatient-list', ['admissions' => $inpatients])
        </section>
    </div>
</x-layouts.app>
