@php $patient = $admission->patient; @endphp
<x-layouts.app title="Inpatient report">
    <x-page-header title="Inpatient report" :description="$admission->facility->name">
        <x-slot:breadcrumb><a href="{{ route('admissions.show', $admission) }}" class="hover:text-brand-700">Inpatient chart</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Report</x-slot:breadcrumb>
        <x-slot:actions><button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" class="h-4 w-4" /> Print</button></x-slot:actions>
    </x-page-header>

    <article class="panel mx-auto max-w-4xl">
        <header class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
                <div><p class="font-semibold">{{ $admission->facility->name }}</p><p class="text-[13px] text-muted">{{ $systemName }}</p></div>
            </div>
            <x-status :value="$admission->status" />
        </header>

        <section class="border-b border-line px-6 py-4">
            <dl class="detail-list lg:grid-cols-4">
                <div><dt>Patient</dt><dd class="font-medium">{{ $patient->full_name }}</dd></div>
                <div><dt>Passport number</dt><dd class="mono">{{ $patient->passport_number }}</dd></div>
                <div><dt>Age and sex</dt><dd>{{ $patient->age }} years, {{ $patient->sex->label() }}</dd></div>
                <div><dt>District</dt><dd>{{ $patient->district?->name }}</dd></div>
                <div><dt>Admitted</dt><dd>{{ $admission->admitted_at->format('j M Y H:i') }}</dd></div>
                <div><dt>Admitted by</dt><dd>{{ $admission->admittedBy?->name }}</dd></div>
                <div><dt>Ward and bed</dt><dd>{{ $admission->ward ? $admission->ward->name.', bed '.$admission->bed?->bed_number : 'Not allocated' }}</dd></div>
                <div><dt>Length of stay</dt><dd>{{ $admission->lengthOfStayInDays() }} days</dd></div>
                <div><dt>Discharged</dt><dd>{{ $admission->discharged_at?->format('j M Y H:i') ?? 'Still admitted' }}</dd></div>
                <div><dt>Discharged by</dt><dd>{{ $admission->dischargedBy?->name }}</dd></div>
                <div><dt>Outcome</dt><dd>{{ $admission->discharge_outcome?->label() }}</dd></div>
            </dl>
        </section>

        <section class="grid gap-4 border-b border-line px-6 py-4">
            <div><h2 class="text-sm font-semibold">Reason for admission</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $admission->admission_reason }}</p></div>
            @if ($admission->visit?->diagnosis)<div><h2 class="text-sm font-semibold">Diagnosis at admission</h2><p class="mt-1 text-sm">{{ $admission->visit->diagnosis }}</p></div>@endif
            @if ($admission->discharge_summary)<div><h2 class="text-sm font-semibold">Discharge summary</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $admission->discharge_summary }}</p></div>@endif
            @if ($admission->follow_up_instructions)<div><h2 class="text-sm font-semibold">Follow up instructions</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $admission->follow_up_instructions }}</p></div>@endif
        </section>

        <section class="border-b border-line px-6 py-4">
            <h2 class="mb-2 text-sm font-semibold">Vital signs</h2>
            @if ($admission->vitals->isEmpty())
                <p class="text-sm text-muted">None recorded.</p>
            @else
                <table class="table border border-line">
                    <thead><tr><th>Recorded</th><th class="text-right">Temperature</th><th class="text-right">Blood pressure</th><th class="text-right">Pulse</th><th class="text-right">Oxygen</th></tr></thead>
                    <tbody>
                        @foreach ($admission->vitals->sortBy('recorded_at') as $vital)
                            <tr><td>{{ $vital->recorded_at->format('j M H:i') }}</td><td class="text-right tabular-nums">{{ $vital->temperature }} °C</td><td class="text-right tabular-nums">{{ $vital->bloodPressure() }}</td><td class="text-right tabular-nums">{{ $vital->pulse_rate }}</td><td class="text-right tabular-nums">{{ $vital->oxygen_saturation ? $vital->oxygen_saturation.'%' : '' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="border-b border-line px-6 py-4">
            <h2 class="mb-2 text-sm font-semibold">Medication prescribed</h2>
            @php $items = $admission->prescriptions->flatMap->items; @endphp
            @forelse ($items as $item)
                <p class="text-sm"><span class="font-medium">{{ $item->medicine_name }}</span> <span class="text-muted">{{ $item->directions() }}</span></p>
            @empty
                <p class="text-sm text-muted">None.</p>
            @endforelse
            @if ($admission->medicationAdministrations->isNotEmpty())
                <p class="mt-2 text-[13px] text-muted">{{ $admission->medicationAdministrations->count() }} doses were recorded as given on the ward.</p>
            @endif
        </section>

        <section class="border-b border-line px-6 py-4">
            <h2 class="mb-2 text-sm font-semibold">Progress notes</h2>
            @forelse ($admission->progressNotes->sortBy('created_at') as $note)
                <div class="mb-3 last:mb-0">
                    <p class="text-[13px] text-muted">{{ $note->created_at->format('j M Y H:i') }}, {{ $note->author?->name }}</p>
                    <p class="whitespace-pre-line text-sm">{{ $note->note }}</p>
                </div>
            @empty
                <p class="text-sm text-muted">None.</p>
            @endforelse
        </section>

        <footer class="px-6 py-3 text-[12px] text-muted">Report produced {{ now()->format('j F Y H:i') }}. This is an electronic record from the {{ $systemName }}.</footer>
    </article>
</x-layouts.app>
