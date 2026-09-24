<x-layouts.app title="Record vital signs">
    <x-page-header :title="'Vital signs for '.$visit->patient->full_name" :description="'Reason for visit: '.$visit->reason_for_visit.'. After saving, the patient moves to the doctor queue.'">
        <x-slot:breadcrumb><a href="{{ route('visits.queue') }}" class="hover:text-brand-700">Patient queue</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Vital signs</x-slot:breadcrumb>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-4">
        <form method="POST" action="{{ route('vitals.store', $visit) }}" class="panel xl:col-span-3">
            @csrf
            <div class="panel-body">@include('partials.vitals-fields')</div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('visits.queue') }}" class="btn-secondary">Back to queue</a>
                <button type="submit" class="btn-primary">Save and send to doctor</button>
            </div>
        </form>

        <aside class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">Patient</h2></div>
            <dl class="panel-body detail-list !grid-cols-1">
                <div><dt>Passport number</dt><dd class="mono">{{ $visit->patient->passport_number }}</dd></div>
                <div><dt>Age and sex</dt><dd>{{ $visit->patient->age }} years, {{ $visit->patient->sex->label() }}</dd></div>
                <div><dt>Allergies</dt><dd class="{{ $visit->patient->allergies ? 'font-medium text-red-700' : '' }}">{{ $visit->patient->allergies ?? 'None recorded' }}</dd></div>
                @if ($previous)
                    <div><dt>Previous reading</dt><dd>{{ $previous->recorded_at->format('j M Y') }}: {{ $previous->temperature }} °C, {{ $previous->weight }} kg{{ $previous->bloodPressure() ? ', '.$previous->bloodPressure() : '' }}</dd></div>
                @endif
            </dl>
        </aside>
    </div>
</x-layouts.app>
