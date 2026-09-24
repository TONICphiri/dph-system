@php use App\Enums\AppointmentStatus; @endphp
<x-layouts.app title="My appointments">
    <x-page-header title="My appointments" description="Requests for you and your children. You are notified when a facility approves or declines a request.">
        <x-slot:actions><a href="{{ route('portal.appointments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Book appointment</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        @forelse ($appointments as $appointment)
            <div class="border-b border-line px-5 py-4 last:border-b-0" x-data="{ reviewing: {{ $errors->any() && old('appointment') == $appointment->id ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $appointment->appointment_date->format('l j F Y') }}</p>
                        <p class="text-sm">{{ $appointment->facility->name }}{{ $appointment->doctor ? ', '.$appointment->doctor->name : '' }}</p>
                        <p class="text-sm text-muted">{{ $appointment->reason }}{{ $appointment->patient_id !== auth()->user()->patient->id ? '. For '.$appointment->patient?->full_name : '' }}</p>
                        @if ($appointment->decision_note)<p class="mt-1 text-[13px] text-muted">Message from the facility: {{ $appointment->decision_note }}</p>@endif
                        @if ($appointment->review)<p class="mt-1 text-[13px] text-muted">You rated this visit {{ $appointment->review->rating }} of 5.</p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status :value="$appointment->status" />
                        @if ($appointment->canBeCancelled())
                            <form method="POST" action="{{ route('portal.appointments.cancel', $appointment) }}" onsubmit="return confirm('Cancel this appointment?')">
                                @csrf @method('PATCH')<button type="submit" class="btn-secondary btn-sm">Cancel</button>
                            </form>
                        @endif
                        @if ($appointment->canBeReviewed())
                            <button type="button" class="btn-secondary btn-sm" @click="reviewing = !reviewing">Rate this visit</button>
                        @endif
                    </div>
                </div>
                @if ($appointment->canBeReviewed())
                    <form method="POST" action="{{ route('portal.appointments.review', $appointment) }}" x-show="reviewing" x-cloak class="mt-4 grid gap-4 border border-line bg-paper p-4 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="appointment" value="{{ $appointment->id }}">
                        <fieldset>
                            <legend class="label">Rating</legend>
                            <div class="flex gap-1">
                                @for ($score = 1; $score <= 5; $score++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $score }}" class="peer sr-only" required>
                                        <span class="block w-10 border border-line bg-white py-2 text-center text-sm peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white">{{ $score }}</span>
                                    </label>
                                @endfor
                            </div>
                            <p class="field-help">1 is poor and 5 is excellent.</p>
                        </fieldset>
                        <fieldset>
                            <legend class="label">Would you recommend this doctor?</legend>
                            <div class="flex gap-4 text-sm">
                                <label class="flex items-center gap-2"><input type="radio" name="would_recommend" value="1" required class="text-brand-700"> Yes</label>
                                <label class="flex items-center gap-2"><input type="radio" name="would_recommend" value="0" class="text-brand-700"> No</label>
                            </div>
                        </fieldset>
                        <x-field.textarea name="comment" label="Comment" rows="2" class="sm:col-span-2" />
                        <div class="sm:col-span-2"><button type="submit" class="btn-primary btn-sm">Submit rating</button></div>
                    </form>
                @endif
            </div>
        @empty
            <x-empty title="You have no appointments" icon="calendar"><a href="{{ route('portal.appointments.create') }}" class="link">Book your first appointment</a>.</x-empty>
        @endforelse
        {{ $appointments->links() }}
    </section>
</x-layouts.app>
