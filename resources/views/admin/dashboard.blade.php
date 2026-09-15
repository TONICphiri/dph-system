<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration</p>
            <h2 class="text-2xl font-extrabold leading-tight">System overview</h2>
            <p class="text-sm text-dhp-100">Nationwide access — no facility limits.</p>
        </div>
    </x-slot>

    <section aria-label="National key figures" class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
        <x-dhp-stat label="Facilities active" :value="$stats['activeFacilities'] . ' / ' . $stats['totalFacilities']" hint="Registered hospitals" />
        <x-dhp-stat label="System users" :value="$stats['activeUsers'] . ' / ' . $stats['totalUsers']" hint="Active accounts" />
        <x-dhp-stat label="Health passports" :value="$stats['totalPatients']" :hint="'+' . $stats['patientsToday'] . ' today'" />
        <x-dhp-stat label="Active admissions" :value="$stats['activeAdmissions']" hint="Nationwide" />
        <x-dhp-stat label="Sync pending" :value="$stats['syncPending']" :hint="$stats['syncFailed'] . ' failed'" :tone="($stats['syncFailed'] ?? 0) > 0 ? 'danger' : 'default'" />
    </section>

    <section aria-label="Management" class="dhp-card dhp-card-pad mb-6">
        <h3 class="dhp-section-title">Manage the system</h3>
        <p class="dhp-section-sub">Facilities, staff, national configuration and the audit trail.</p>
        <div class="dhp-steps mt-4 lg:!grid-cols-4">
            <a href="{{ route('facilities.index') }}" class="dhp-step">
                <span class="dhp-step-num">Facilities</span>
                <span class="dhp-step-desc">Create, edit and deactivate hospitals.</span>
            </a>
            <a href="{{ route('users.index') }}" class="dhp-step">
                <span class="dhp-step-num">Users</span>
                <span class="dhp-step-desc">Facility admins and staff accounts.</span>
            </a>
            <a href="{{ route('settings.global.edit') }}" class="dhp-step">
                <span class="dhp-step-num">National config</span>
                <span class="dhp-step-desc">Passport fields, vaccines, templates.</span>
            </a>
            <a href="{{ route('audit-logs.index') }}" class="dhp-step">
                <span class="dhp-step-num">Audit trail</span>
                <span class="dhp-step-desc">Global security and access logs.</span>
            </a>
            @can('manage_global_settings')
                <a href="{{ route('admin.landing-slides.index') }}" class="dhp-step">
                    <span class="dhp-step-num">Landing slides</span>
                    <span class="dhp-step-desc">Slideshow images + rotation time.</span>
                </a>
            @endcan
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section aria-label="Facility performance" class="dhp-card overflow-hidden">
            <div class="dhp-card-pad flex items-center justify-between border-b border-[#E7F0F0]">
                <h3 class="dhp-section-title">Facility performance</h3>
                <a href="{{ route('facilities.index') }}" class="text-sm font-bold text-dhp-700 hover:underline">All facilities →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="dhp-table">
                    <thead><tr><th>Facility</th><th>Staff</th><th>Patients</th><th>Visits</th></tr></thead>
                    <tbody>
                        @forelse($facilities as $facility)
                            <tr>
                                <td class="font-semibold">{{ $facility->name }}</td>
                                <td class="tabular-nums">{{ $facility->total_staff }}</td>
                                <td class="tabular-nums">{{ $facility->total_patients }}</td>
                                <td class="tabular-nums">{{ $facility->total_encounters }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No facilities registered.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-label="Latest audit activity" class="dhp-card dhp-card-pad">
            <div class="flex items-center justify-between">
                <h3 class="dhp-section-title">Latest audit activity</h3>
                <a href="{{ route('audit-logs.index') }}" class="text-sm font-bold text-dhp-700 hover:underline">Full trail →</a>
            </div>
            @if($recentAudit->count())
                <ul class="mt-3 divide-y divide-[#E7F0F0]">
                    @foreach($recentAudit as $log)
                        <li class="flex justify-between gap-3 py-2.5">
                            <span class="text-sm">{{ $log->description ?? $log->action }}</span>
                            <span class="whitespace-nowrap text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="dhp-empty mt-3"><p class="text-sm text-slate-500">No audit entries yet.</p></div>
            @endif
        </section>
    </div>
</x-app-layout>
