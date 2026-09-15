<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Ministry of Health · Malawi</p>
            <h2 class="text-2xl font-extrabold leading-tight">Clinical Dashboard</h2>
            <p class="text-sm text-dhp-100">{{ Auth::user()->facility?->name ?? 'National Registry' }} · {{ now()->format('l, d M Y') }} · Signed in as {{ Auth::user()->name }}</p>
        </div>
    </x-slot>

    {{-- Patient journey: matches the paper health passport flow --}}
    <section aria-label="Patient journey" class="dhp-card dhp-card-pad mb-6">
        <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <p class="dhp-eyebrow">Patient journey</p>
                <h3 class="dhp-section-title">Follow the same flow as the paper health passport</h3>
            </div>
            <p class="text-sm text-slate-500">Only actions allowed for your role are shown.</p>
        </div>
        <div class="dhp-steps">
            @can('create_patient')
                <a href="{{ route('patients.create') }}" class="dhp-step">
                    <span class="dhp-step-num">01 · Reception</span>
                    <span class="dhp-step-title">Register / Find Patient</span>
                    <span class="dhp-step-desc">National ID lookup, DHP ID and QR code.</span>
                </a>
            @endcan
            @can('triage_patient')
                <a href="{{ route('patients.index') }}" class="dhp-step">
                    <span class="dhp-step-num">02 · Triage</span>
                    <span class="dhp-step-title">Record Vitals</span>
                    <span class="dhp-step-desc">Prioritize patients before consultation.</span>
                </a>
            @endcan
            @can('consult_patient')
                <a href="{{ route('patients.index') }}" class="dhp-step">
                    <span class="dhp-step-num">03 · Consultation</span>
                    <span class="dhp-step-title">Diagnose / Prescribe</span>
                    <span class="dhp-step-desc">History, diagnosis, outpatient or admission.</span>
                </a>
            @endcan
            @can('dispense_medication')
                <a href="{{ route('patients.index') }}" class="dhp-step">
                    <span class="dhp-step-num">04 · Pharmacy</span>
                    <span class="dhp-step-title">Dispense Medication</span>
                    <span class="dhp-step-desc">Fulfill prescriptions, update stock.</span>
                </a>
            @endcan
            @can('update_patient')
                <a href="{{ route('patients.index') }}" class="dhp-step">
                    <span class="dhp-step-num">05 · Ward</span>
                    <span class="dhp-step-title">Inpatient Care</span>
                    <span class="dhp-step-desc">Ward notes, vitals, med administration.</span>
                </a>
            @endcan
        </div>
    </section>

    {{-- User Catalogue & verification (system-description2.md): NIN enrollment,
         approvals, identity desk and QR checks for facility roles --}}
    @canany(['enroll_patient', 'approve_enrollment', 'verify_identity', 'verify_credential'])
        <section aria-label="Catalogue and verification" class="dhp-card dhp-card-pad mb-6">
            <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <p class="dhp-eyebrow">User Catalogue</p>
                    <h3 class="dhp-section-title">Enrollment & verification</h3>
                </div>
                <p class="text-sm text-slate-500">NIN-keyed accounts, facility approvals, identity desk.</p>
            </div>
            <div class="dhp-steps lg:!grid-cols-4">
                @canany(['enroll_patient', 'create_patient'])
                    <a href="{{ route('enroll.create') }}" class="dhp-step">
                        <span class="dhp-step-num">Enroll</span>
                        <span class="dhp-step-title">Enroll user with NIN</span>
                        <span class="dhp-step-desc">Physical ID check → PENDING approval.</span>
                    </a>
                @endcanany
                @can('approve_enrollment')
                    <a href="{{ route('facility.approvals') }}" class="dhp-step">
                        <span class="dhp-step-num">Approve</span>
                        <span class="dhp-step-title">Approval queue</span>
                        <span class="dhp-step-desc">Approve or reject pending enrollments.</span>
                    </a>
                @endcan
                @can('verify_identity')
                    <a href="{{ route('facility.identity-services') }}" class="dhp-step">
                        <span class="dhp-step-num">Identity desk</span>
                        <span class="dhp-step-title">Resets & recovery</span>
                        <span class="dhp-step-desc">Verified person present → log recovery.</span>
                    </a>
                @endcan
                @can('verify_credential')
                    <a href="{{ route('verify.scan') }}" class="dhp-step">
                        <span class="dhp-step-num">Verify</span>
                        <span class="dhp-step-title">Check QR credential</span>
                        <span class="dhp-step-desc">Minimal verification-level result.</span>
                    </a>
                @endcan
            </div>
        </section>
    @endcanany

    {{-- Key figures --}}
    <section aria-label="Key figures" class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        <x-dhp-stat label="Total patients" :value="$stats['totalPatients']" hint="Lifetime registry" />
        <x-dhp-stat label="Registered today" :value="$stats['patientsToday']" hint="New passports issued" />
        <x-dhp-stat label="Awaiting triage" :value="$stats['pendingTriage']" hint="Status: registered" />
        <x-dhp-stat label="In consultation queue" :value="$stats['awaitingConsultation']" hint="Triaged + active" />
        <x-dhp-stat label="Active admissions" :value="$stats['activeAdmissions']" hint="Currently on wards" />
        <x-dhp-stat label="Admitted today" :value="$stats['admittedToday']" hint="New admissions" />
        <x-dhp-stat label="Low / out of stock" :value="$stats['lowStock']" hint="Needs restocking" tone="{{ ($stats['lowStock'] ?? 0) > 0 ? 'danger' : 'default' }}" />
        @can('view_sync_queue')
            <div class="dhp-stat">
                <p class="dhp-stat-label">National sync</p>
                <p class="mt-1 flex items-center gap-2">
                    <span class="dhp-badge {{ $syncStats['online'] ? 'badge-active' : 'badge-pending' }}">{{ $syncStats['online'] ? 'Online' : 'Offline mode' }}</span>
                </p>
                <p class="mt-2 text-xs text-slate-500 tabular-nums">{{ $syncStats['synced'] }} synced · {{ $syncStats['pending'] }} pending · {{ $syncStats['failed'] }} failed</p>
                <a href="{{ route('sync.status') }}" class="mt-2 inline-block text-sm font-bold text-dhp-700 hover:underline">View sync queue →</a>
            </div>
        @endcan
    </section>

    {{-- Consultation queue --}}
    <section aria-label="Consultation queue" class="dhp-card mb-6 overflow-hidden">
        <div class="dhp-card-pad flex items-center justify-between border-b border-[#E7F0F0]">
            <div>
                <h3 class="dhp-section-title">Consultation queue</h3>
                <p class="dhp-section-sub">Emergency first, then longest waiting. Vitals drive priority automatically.</p>
            </div>
            <span class="dhp-badge badge-neutral">{{ $consultationQueue->count() }} waiting</span>
        </div>
        @if ($consultationQueue->count())
            <div class="overflow-x-auto">
                <table class="dhp-table">
                    <thead><tr><th>Priority</th><th>DHP ID</th><th>Patient</th><th>Vitals</th><th>Waiting</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($consultationQueue as $encounter)
                            @php $priority = $encounter->vitals->first()?->priority_level ?? 'Low'; @endphp
                            <tr>
                                <td><x-priority-badge :level="$priority" /></td>
                                <td class="dhp-mono">{{ $encounter->patient->dhp_id }}</td>
                                <td>
                                    <a href="{{ route('patients.show', $encounter->patient) }}" class="font-bold text-dhp-800 hover:underline">{{ $encounter->patient->full_name }}</a>
                                    <span class="block text-xs text-slate-500">Age {{ $encounter->patient->age ?? 'N/A' }} · {{ $encounter->patient->gender ?? '—' }}</span>
                                </td>
                                <td class="text-xs text-slate-600">
                                    @if ($latestVital = $encounter->vitals->first())
                                        T:{{ $latestVital->temperature ?? '—' }}°C · HR:{{ $latestVital->heart_rate ?? '—' }} · SpO₂:{{ $latestVital->oxygen_saturation ?? '—' }}%
                                    @else
                                        <span class="text-slate-400">No vitals yet</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs text-slate-500">{{ $encounter->encounter_date->diffForHumans() }}</td>
                                <td class="whitespace-nowrap">
                                    @can('consult_patient')
                                        <a href="{{ route('consultation', $encounter->patient) }}" class="btn-success !min-h-[40px] !px-3 !py-1.5 !text-xs">Start consultation</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dhp-card-pad"><div class="dhp-empty"><p class="font-bold text-dhp-900">No patients waiting</p><p class="text-sm text-slate-500">New triage records will appear here automatically.</p></div></div>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-label="Quick actions" class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Quick actions</h3>
            <p class="dhp-section-sub">Jump straight to the next clinical task.</p>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @can('create_patient')<a href="{{ route('patients.create') }}" class="btn-primary">Register patient</a>@endcan
                @can('view_patients')<a href="{{ route('patients.index') }}" class="btn-secondary">Patient registry</a>@endcan
                @can('manage_inventory')<a href="{{ route('inventory.index') }}" class="btn-secondary">Pharmacy stock</a>@endcan
                @can('view_reports')<a href="{{ route('reports.index') }}" class="btn-secondary">Reports</a>@endcan
            </div>
        </section>

        <section aria-label="Recent encounters" class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Recent encounters</h3>
            <p class="dhp-section-sub">Latest clinical activity at this facility.</p>
            @if ($recentEncounters->count())
                <ul class="mt-3 divide-y divide-[#E7F0F0]">
                    @foreach ($recentEncounters as $encounter)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <a href="{{ route('patients.show', $encounter->patient) }}" class="font-semibold text-dhp-800 hover:underline">{{ $encounter->patient->full_name }}</a>
                            <span class="text-xs text-slate-500">{{ ucfirst($encounter->encounter_type) }} · {{ $encounter->encounter_date->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="dhp-empty mt-3"><p class="text-sm text-slate-500">No encounters recorded yet.</p></div>
            @endif
        </section>
    </div>

    <section aria-label="Recently registered patients" class="dhp-card mt-6 overflow-hidden">
        <div class="dhp-card-pad border-b border-[#E7F0F0]">
            <h3 class="dhp-section-title">Recently registered patients</h3>
            <p class="dhp-section-sub">Newest passports first.</p>
        </div>
        @if ($recentPatients->count())
            <div class="overflow-x-auto">
                <table class="dhp-table">
                    <thead><tr><th>DHP ID</th><th>Name</th><th>National ID</th><th>Status</th><th>Registered</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($recentPatients as $patient)
                            <tr>
                                <td class="dhp-mono">{{ $patient->dhp_id }}</td>
                                <td class="font-semibold">{{ $patient->full_name }}</td>
                                <td>{{ $patient->national_id ?? '—' }}</td>
                                <td><span class="dhp-badge {{ $patient->status === 'active' ? 'badge-active' : 'badge-pending' }}">{{ ucfirst($patient->status) }}</span></td>
                                <td class="text-xs text-slate-500">{{ $patient->registered_at?->format('d M Y') }}</td>
                                <td><a href="{{ route('patients.show', $patient) }}" class="font-bold text-dhp-700 hover:underline">Open →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dhp-card-pad"><div class="dhp-empty"><p class="text-sm text-slate-500">No patients registered yet.</p></div></div>
        @endif
    </section>
</x-app-layout>
