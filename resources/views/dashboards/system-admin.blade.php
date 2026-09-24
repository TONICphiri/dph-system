<x-layouts.app title="Dashboard">
    <x-page-header title="National overview" description="Facilities, accounts and the condition of the system across the country.">
        <x-slot:actions>
            <a href="{{ route('admin.facilities.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Register facility</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active facilities" :value="number_format($stats['facilities'])" icon="building" :href="route('admin.facilities.index')" />
        <x-stat label="Facility administrators" :value="number_format($stats['facilityAdmins'])" icon="users" :href="route('admin.facility-administrators.index')" />
        <x-stat label="Health workers" :value="number_format($stats['staff'])" icon="stethoscope" />
        <x-stat label="Registered patients" :value="number_format($stats['patients'])" icon="heart" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="panel xl:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Recently registered facilities</h2>
                <a href="{{ route('admin.facilities.index') }}" class="link text-sm">All facilities</a>
            </div>
            @if ($facilities->isEmpty())
                <x-empty title="No facilities yet" icon="building">Register the first hospital or health centre to begin.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Facility</th><th>District</th><th class="text-right">Staff</th><th class="text-right">Patients</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($facilities as $facility)
                                <tr>
                                    <td><a href="{{ route('admin.facilities.show', $facility) }}" class="font-medium hover:text-brand-700 hover:underline">{{ $facility->name }}</a><p class="mono text-muted">{{ $facility->code }}</p></td>
                                    <td>{{ $facility->district->name }}</td>
                                    <td class="text-right tabular-nums">{{ $facility->users_count }}</td>
                                    <td class="text-right tabular-nums">{{ $facility->patients_count }}</td>
                                    <td><x-status :value="$facility->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">System health</h2>
                <a href="{{ route('admin.system-health') }}" class="link text-sm">Details</a>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($checks as $check)
                    <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm">
                        <span class="text-muted">{{ $check['label'] }}</span>
                        <span class="flex items-center gap-2 text-right">
                            {{ $check['value'] }}
                            <span class="h-2.5 w-2.5 {{ $check['healthy'] ? 'bg-brand-600' : 'bg-red-700' }}" title="{{ $check['healthy'] ? 'Working' : 'Needs attention' }}"></span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="panel mt-6">
        <div class="panel-header">
            <h2 class="panel-title">Recent activity</h2>
            <a href="{{ route('audit-log.index') }}" class="link text-sm">Activity log</a>
        </div>
        @include('partials.activity-list', ['activity' => $activity])
    </section>
</x-layouts.app>
