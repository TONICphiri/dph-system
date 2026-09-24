<x-layouts.app title="My health">
    <x-page-header :title="'Welcome, '.$patient->first_name" description="Your health passport, appointments and reminders.">
        <x-slot:actions>
            <a href="{{ route('portal.appointments.create') }}" class="btn-primary"><x-icon name="calendar" class="h-4 w-4" /> Book appointment</a>
            <a href="{{ route('portal.records') }}" class="btn-secondary"><x-icon name="heart" class="h-4 w-4" /> My records</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="panel border-t-4 border-t-brand-700">
            <div class="panel-header"><h2 class="panel-title">Health passport</h2><a href="{{ route('portal.card') }}" class="link text-sm">View card</a></div>
            <dl class="panel-body detail-list !grid-cols-1">
                <div><dt>Passport number</dt><dd class="mono text-base">{{ $patient->passport_number }}</dd></div>
                <div><dt>National ID</dt><dd class="mono">{{ $patient->national_id ?? 'Not recorded' }}</dd></div>
                <div><dt>Date of birth</dt><dd>{{ $patient->date_of_birth->format('j F Y') }} ({{ $patient->age_label }})</dd></div>
                <div><dt>Blood group</dt><dd>{{ $patient->blood_group ?? 'Not recorded' }}</dd></div>
                <div><dt>Registered at</dt><dd>{{ $patient->registeredFacility?->name ?? 'Not recorded' }}</dd></div>
            </dl>
            @if ($patient->children->isNotEmpty())
                <div class="border-t border-line px-5 py-3">
                    <p class="text-[12px] font-medium uppercase tracking-wide text-muted">Children linked to you</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($patient->children as $child)
                            <li><a href="{{ route('portal.records', ['patient' => $child->id]) }}" class="link">{{ $child->full_name }}</a> <span class="text-muted">({{ $child->age_label }})</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Upcoming appointments</h2><a href="{{ route('portal.appointments.index') }}" class="link text-sm">All</a></div>
            @forelse ($upcomingAppointments as $appointment)
                <div class="border-b border-line px-5 py-3 last:border-b-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium">{{ $appointment->appointment_date->format('l j F') }}</p>
                        <x-status :value="$appointment->status" />
                    </div>
                    <p class="text-[13px] text-muted">{{ $appointment->facility->name }}{{ $appointment->doctor ? ', '.$appointment->doctor->name : '' }}</p>
                </div>
            @empty
                <x-empty title="No upcoming appointments" icon="calendar">
                    <a href="{{ route('portal.appointments.create') }}" class="link">Book an appointment</a>
                </x-empty>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Reminders</h2></div>
            @forelse ($reminders as $reminder)
                <div class="border-b border-line px-5 py-3 last:border-b-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium">{{ $reminder->title }}</p>
                        <span class="text-[13px] {{ $reminder->due_on->isPast() ? 'text-red-700' : 'text-muted' }}">{{ $reminder->due_on->isPast() && ! $reminder->due_on->isToday() ? 'Overdue, ' : '' }}{{ $reminder->due_on->format('j M') }}</span>
                    </div>
                    <p class="text-[13px] text-muted">{{ $reminder->patient_id !== $patient->id ? 'For '.$reminder->patient->first_name.'. ' : '' }}{{ $reminder->message }}</p>
                </div>
            @empty
                <x-empty title="You have no reminders" icon="bell" />
            @endforelse
        </section>
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Recent visits</h2><a href="{{ route('portal.records') }}" class="link text-sm">Full history</a></div>
        @if ($recentVisits->isEmpty())
            <x-empty title="No visits recorded yet" icon="clipboard" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Facility</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentVisits as $visit)
                            <tr>
                                <td class="whitespace-nowrap">{{ $visit->checked_in_at->format('j M Y') }}</td>
                                <td>{{ $visit->facility->name }}</td>
                                <td>{{ $visit->reason_for_visit }}</td>
                                <td><x-status :value="$visit->status" /></td>
                                <td class="text-right">@can('viewReport', $visit)<a href="{{ route('visits.report', $visit) }}" class="link text-sm">Report</a>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
