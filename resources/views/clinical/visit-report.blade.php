@php $patient = $visit->patient; $vital = $visit->vitals->last(); @endphp
<x-layouts.app title="Visit report">
    <x-page-header title="Outpatient visit report" :description="$visit->facility->name.', '.$visit->checked_in_at->format('j F Y')">
        <x-slot:breadcrumb><a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Visit report</x-slot:breadcrumb>
        <x-slot:actions><button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" class="h-4 w-4" /> Print</button></x-slot:actions>
    </x-page-header>

    <article class="panel mx-auto max-w-4xl">
        <header class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
                <div><p class="font-semibold">{{ $visit->facility->name }}</p><p class="text-[13px] text-muted">{{ $systemName }}</p></div>
            </div>
            <x-status :value="$visit->status" />
        </header>

        <section class="border-b border-line px-6 py-4">
            <dl class="detail-list lg:grid-cols-4">
                <div><dt>Patient</dt><dd class="font-medium">{{ $patient->full_name }}</dd></div>
                <div><dt>Passport number</dt><dd class="mono">{{ $patient->passport_number }}</dd></div>
                <div><dt>Age and sex</dt><dd>{{ $patient->age_label }}, {{ $patient->sex->label() }}</dd></div>
                <div><dt>District</dt><dd>{{ $patient->district?->name }}</dd></div>
                <div><dt>Checked in</dt><dd>{{ $visit->checked_in_at->format('j M Y H:i') }}</dd></div>
                <div><dt>Seen by</dt><dd>{{ $visit->doctor?->name ?? 'Not seen' }}</dd></div>
                <div><dt>Type of care</dt><dd>{{ $visit->care_type?->label() }}</dd></div>
                <div><dt>Completed</dt><dd>{{ $visit->completed_at?->format('j M Y H:i') ?? 'In progress' }}</dd></div>
            </dl>
        </section>

        @if ($vital)
            <section class="border-b border-line px-6 py-4">
                <h2 class="mb-2 text-sm font-semibold">Vital signs</h2>
                <p class="text-sm">Temperature {{ $vital->temperature }} °C. Weight {{ $vital->weight }} kg.{{ $vital->bloodPressure() ? ' Blood pressure '.$vital->bloodPressure().'.' : '' }}{{ $vital->pulse_rate ? ' Pulse '.$vital->pulse_rate.' per minute.' : '' }}{{ $vital->oxygen_saturation ? ' Oxygen saturation '.$vital->oxygen_saturation.'%.' : '' }} <span class="text-muted">Recorded by {{ $vital->recordedBy?->name }}.</span></p>
            </section>
        @endif

        <section class="grid gap-4 border-b border-line px-6 py-4 sm:grid-cols-2">
            <div><h2 class="text-sm font-semibold">Reason for visit</h2><p class="mt-1 text-sm">{{ $visit->reason_for_visit }}</p></div>
            <div><h2 class="text-sm font-semibold">Diagnosis</h2><p class="mt-1 text-sm">{{ $visit->diagnosis ?? 'Not recorded' }}</p></div>
            <div class="sm:col-span-2"><h2 class="text-sm font-semibold">History</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $visit->history }}</p></div>
            @if ($visit->examination)<div class="sm:col-span-2"><h2 class="text-sm font-semibold">Examination</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $visit->examination }}</p></div>@endif
            @if ($visit->treatment_plan)<div class="sm:col-span-2"><h2 class="text-sm font-semibold">Treatment plan</h2><p class="mt-1 whitespace-pre-line text-sm">{{ $visit->treatment_plan }}</p></div>@endif
        </section>

        <section class="border-b border-line px-6 py-4">
            <h2 class="mb-2 text-sm font-semibold">Medication</h2>
            @forelse ($visit->prescriptions as $prescription)
                <table class="table border border-line">
                    <thead><tr><th>Medicine</th><th>Directions</th><th class="text-right">Quantity</th></tr></thead>
                    <tbody>
                        @foreach ($prescription->items as $item)
                            <tr><td class="font-medium">{{ $item->medicine_name }}</td><td>{{ $item->directions() }}</td><td class="text-right tabular-nums">{{ $item->quantity }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-[13px] text-muted"><x-status :value="$prescription->status" /> {{ $prescription->dispenser ? 'Dispensed by '.$prescription->dispenser->name.' on '.$prescription->dispensed_at?->format('j M Y H:i').'.' : '' }}</p>
            @empty
                <p class="text-sm text-muted">No medicine was prescribed.</p>
            @endforelse
        </section>

        @if ($visit->admission)
            <section class="border-b border-line px-6 py-4 text-sm">
                The patient was admitted on {{ $visit->admission->admitted_at->format('j M Y') }}.
                <a href="{{ route('admissions.show', $visit->admission) }}" class="link no-print">Open the admission</a>
            </section>
        @endif

        <footer class="px-6 py-3 text-[12px] text-muted">Report produced {{ now()->format('j F Y H:i') }}. This is an electronic record from the {{ $systemName }}.</footer>
    </article>
</x-layouts.app>
