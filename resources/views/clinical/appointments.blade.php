@php use App\Enums\AppointmentStatus; @endphp
<x-layouts.app title="Appointments">
    <x-page-header title="Appointments" description="Bookings made by patients through the portal. The patient is notified of every decision." />
    <section class="panel">
        <div class="flex overflow-x-auto border-b border-line">
            @foreach ($statuses as $value => $label)
                <a href="{{ route('appointments.index', ['status' => $value]) }}" class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium {{ $status === $value ? 'border-brand-700 text-brand-800' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-line px-5 py-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div><label for="date" class="label">Date</label><input id="date" type="date" name="date" value="{{ request('date') }}" class="input"></div>
            <button type="submit" class="btn-secondary">Filter</button>
            @if (request('date'))<a href="{{ route('appointments.index', ['status' => $status]) }}" class="link text-sm">Clear</a>@endif
        </form>

        @forelse ($appointments as $appointment)
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line px-5 py-4" x-data="{ declining: false }">
                <div class="min-w-[220px]">
                    <x-patient-cell :patient="$appointment->patient" />
                    <p class="mt-1 text-sm"><span class="font-medium">{{ $appointment->appointment_date->format('l j F Y') }}</span> with {{ $appointment->doctor?->name }}</p>
                    <p class="text-sm text-muted">{{ $appointment->reason }}</p>
                    @if ($appointment->decision_note)<p class="mt-1 text-[13px] text-muted">Note: {{ $appointment->decision_note }}</p>@endif
                    @if ($appointment->review)
                        <p class="mt-1 text-[13px] text-muted">Patient rating {{ $appointment->review->rating }} of 5{{ $appointment->review->would_recommend ? ', would recommend' : '' }}.</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-start gap-2">
                    <x-status :value="$appointment->status" />
                    @can('decide', $appointment)
                        @if ($appointment->status === AppointmentStatus::Pending)
                            <form method="POST" action="{{ route('appointments.decide', $appointment) }}">
                                @csrf<input type="hidden" name="decision" value="approved">
                                <button type="submit" class="btn-primary btn-sm"><x-icon name="check" class="h-4 w-4" /> Approve</button>
                            </form>
                            <button type="button" class="btn-secondary btn-sm" @click="declining = !declining">Decline</button>
                        @elseif ($appointment->status === AppointmentStatus::Approved && ! $appointment->appointment_date->isFuture())
                            <form method="POST" action="{{ route('appointments.complete', $appointment) }}">
                                @csrf<button type="submit" class="btn-secondary btn-sm">Mark as attended</button>
                            </form>
                        @endif
                    @endcan
                </div>
                @if ($appointment->status === AppointmentStatus::Pending)
                    <form method="POST" action="{{ route('appointments.decide', $appointment) }}" x-show="declining" x-cloak class="flex w-full flex-wrap items-end gap-2">
                        @csrf<input type="hidden" name="decision" value="declined">
                        <div class="min-w-[240px] flex-1"><label class="label" for="decision_note_{{ $appointment->id }}">Reason for declining</label><input id="decision_note_{{ $appointment->id }}" name="decision_note" class="input" required placeholder="For example, the doctor is attending a training that day"></div>
                        <button type="submit" class="btn-danger btn-sm">Decline appointment</button>
                    </form>
                @endif
            </div>
        @empty
            <x-empty title="No appointments here" icon="calendar" />
        @endforelse
        {{ $appointments->links() }}
    </section>
</x-layouts.app>
