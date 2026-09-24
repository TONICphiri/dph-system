@php $viewingChild = $patient->id !== $owner->id; @endphp
<x-layouts.app title="My health records">
    <x-page-header :title="$viewingChild ? 'Health records of '.$patient->full_name : 'My health records'" :description="'Passport number '.$patient->passport_number.'. Records from every facility you have visited.'">
        <x-slot:actions>
            <a href="{{ route('portal.card', $viewingChild ? ['patient' => $patient->id] : []) }}" class="btn-secondary" target="_blank"><x-icon name="qr" class="h-4 w-4" /> Passport card</a>
            <a href="{{ route('portal.appointments.create') }}" class="btn-primary"><x-icon name="calendar" class="h-4 w-4" /> Book appointment</a>
        </x-slot:actions>
    </x-page-header>

    @if ($family->count() > 1)
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Family members">
            @foreach ($family as $member)
                <a href="{{ route('portal.records', $member->id === $owner->id ? [] : ['patient' => $member->id]) }}"
                    class="border px-3 py-2 text-sm {{ $member->id === $patient->id ? 'border-brand-700 bg-brand-700 text-white' : 'border-line bg-white hover:border-brand-600' }}">
                    {{ $member->id === $owner->id ? 'Me' : $member->first_name.', '.$member->age_label }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Visits</h2></div>
                @forelse ($visits as $visit)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium">{{ $visit->checked_in_at->format('j F Y') }}, {{ $visit->facility->name }}</p>
                            <x-status :value="$visit->status" />
                        </div>
                        <p class="text-sm text-muted">{{ $visit->reason_for_visit }}{{ $visit->doctor ? '. Seen by '.$visit->doctor->name : '' }}.</p>
                        @if ($visit->diagnosis)<p class="mt-1 text-sm">Diagnosis: {{ $visit->diagnosis }}</p>@endif
                        @if ($visit->treatment_plan)<p class="text-sm text-muted">Advice: {{ $visit->treatment_plan }}</p>@endif
                    </div>
                @empty
                    <x-empty title="No visits recorded yet" icon="clipboard" />
                @endforelse
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Medication</h2></div>
                @forelse ($prescriptions as $prescription)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <div class="flex items-center justify-between gap-2 text-[13px] text-muted"><span>{{ $prescription->created_at->format('j F Y') }}, {{ $prescription->facility->name }}</span><x-status :value="$prescription->status" /></div>
                        <ul class="mt-1 text-sm">@foreach ($prescription->items as $item)<li><span class="font-medium">{{ $item->medicine_name }}</span> <span class="text-muted">{{ $item->directions() }}</span></li>@endforeach</ul>
                    </div>
                @empty
                    <x-empty title="No medication recorded" icon="pill" />
                @endforelse
            </section>

            @if ($admissions->isNotEmpty())
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Hospital stays</h2></div>
                    @foreach ($admissions as $admission)
                        <div class="border-b border-line px-5 py-3 last:border-b-0">
                            <p class="font-medium">{{ $admission->facility->name }}{{ $admission->ward ? ', '.$admission->ward->name : '' }}</p>
                            <p class="text-sm text-muted">{{ $admission->admitted_at->format('j M Y') }} to {{ $admission->discharged_at?->format('j M Y') ?? 'present' }}. {{ $admission->discharge_outcome?->label() }}</p>
                            @if ($admission->follow_up_instructions)<p class="mt-1 text-sm">Follow up: {{ $admission->follow_up_instructions }}</p>@endif
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Upcoming reminders</h2></div>
                @forelse ($reminders as $reminder)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <p class="font-medium">{{ $reminder->title }}</p>
                        <p class="text-sm text-muted">{{ $reminder->message }}</p>
                        <p class="mt-1 text-[13px] {{ $reminder->due_on->isPast() ? 'font-medium text-red-700' : 'text-muted' }}">{{ $reminder->due_on->isPast() && ! $reminder->due_on->isToday() ? 'Overdue since' : 'Due' }} {{ $reminder->due_on->format('j F Y') }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">No reminders at the moment.</p>
                @endforelse
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Vaccinations</h2></div>
                @forelse ($vaccinations as $vaccination)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <p class="font-medium">{{ $vaccination->vaccine->name }}, dose {{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</p>
                        <p class="text-[13px] text-muted">{{ $vaccination->administered_on->format('j M Y') }}, {{ $vaccination->facility?->name }}</p>
                        @if ($vaccination->next_dose_due_on)<p class="text-[13px] text-brand-700">Next dose due {{ $vaccination->next_dose_due_on->format('j M Y') }}</p>@endif
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">No vaccinations recorded.</p>
                @endforelse
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Emergency contacts</h2></div>
                @forelse ($patient->emergencyContacts as $contact)
                    <div class="border-b border-line px-5 py-3 last:border-b-0"><p class="font-medium">{{ $contact->full_name }} <span class="font-normal text-muted">{{ $contact->relationship }}</span></p><p class="text-sm">{{ $contact->phone }}</p></div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">None recorded. Ask the registration desk to add a contact.</p>
                @endforelse
            </section>
        </aside>
    </div>
</x-layouts.app>
