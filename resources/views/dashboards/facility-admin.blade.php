<x-layouts.app title="Dashboard">
    <x-page-header :title="auth()->user()->facility->name" description="Staff, beds, appointments and stock at your facility today.">
        <x-slot:actions>
            <a href="{{ route('facility.staff.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register health worker</a>
            <a href="{{ route('facility.reports') }}" class="btn-secondary"><x-icon name="chart" class="h-4 w-4" /> Reports</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active health workers" :value="$stats['staff']" icon="users" :href="route('facility.staff.index')" />
        <x-stat label="Visits today" :value="$stats['visitsToday']" icon="clipboard" />
        <x-stat label="Beds available" :value="$stats['bedsAvailable'].' of '.$stats['bedsTotal']" icon="bed" :href="route('facility.bed-board')"
            :tone="$stats['bedsTotal'] > 0 && $stats['bedsAvailable'] === 0 ? 'danger' : 'default'" :hint="$stats['inpatients'].' patients admitted'" />
        <x-stat label="Appointments to approve" :value="$stats['pendingAppointments']" icon="calendar" :href="route('appointments.index')"
            :tone="$stats['pendingAppointments'] > 0 ? 'warning' : 'default'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Patients waiting for a bed</h2>
                <a href="{{ route('admissions.index') }}" class="link text-sm">All admissions</a>
            </div>
            @forelse ($awaitingBed as $admission)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <x-patient-cell :patient="$admission->patient" :link="false" />
                    <div class="text-right">
                        <p class="text-[13px] text-muted">Admitted {{ $admission->admitted_at->diffForHumans() }}</p>
                        <a href="{{ route('admissions.show', $admission) }}" class="link text-sm">Allocate bed</a>
                    </div>
                </div>
            @empty
                <x-empty title="No patients are waiting for a bed" icon="bed" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Appointment requests</h2>
                <a href="{{ route('appointments.index') }}" class="link text-sm">All requests</a>
            </div>
            @forelse ($appointments as $appointment)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <div>
                        <p class="font-medium">{{ $appointment->patient->full_name }}</p>
                        <p class="text-[13px] text-muted">{{ $appointment->appointment_date->format('D j M Y') }} with {{ $appointment->doctor?->name ?? 'any doctor' }}</p>
                    </div>
                    <x-status :value="$appointment->status" />
                </div>
            @empty
                <x-empty title="No requests waiting for approval" icon="calendar" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Ward occupancy</h2>
                <a href="{{ route('facility.wards.index') }}" class="link text-sm">Manage wards</a>
            </div>
            @forelse ($wards as $ward)
                @php $percent = $ward->beds_count ? round($ward->occupied_count / $ward->beds_count * 100) : 0; @endphp
                <div class="border-b border-line px-5 py-3 last:border-b-0">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ $ward->name }}</span>
                        <span class="tabular-nums text-muted">{{ $ward->occupied_count }} of {{ $ward->beds_count }} beds in use</span>
                    </div>
                    <div class="mt-2 h-2 bg-paper" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $ward->name }} occupancy">
                        <div class="h-2 {{ $percent >= 90 ? 'bg-red-700' : ($percent >= 70 ? 'bg-gold-600' : 'bg-brand-600') }}" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <x-empty title="No wards have been set up" icon="door">Add wards and beds before admitting patients.</x-empty>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Medicines running low</h2>
                <a href="{{ route('medicines.index') }}" class="link text-sm">Medicine stock</a>
            </div>
            @forelse ($lowStock as $medicine)
                <div class="flex items-center justify-between border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <span class="font-medium">{{ $medicine->displayName() }}</span>
                    <span class="badge-danger tabular-nums">{{ $medicine->stock_quantity }} left</span>
                </div>
            @empty
                <x-empty title="All medicines are above their reorder level" icon="box" />
            @endforelse
        </section>
    </div>
</x-layouts.app>
