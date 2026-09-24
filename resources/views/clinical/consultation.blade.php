@php $vital = $visit->vitals->last(); @endphp
<x-layouts.app title="Consultation">
    <x-page-header :title="'Consultation: '.$patient->full_name" :description="'Reason for visit: '.$visit->reason_for_visit.'.'">
        <x-slot:breadcrumb><a href="{{ route('visits.queue') }}" class="hover:text-brand-700">Patient queue</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Consultation</x-slot:breadcrumb>
        <x-slot:actions><a href="{{ route('patients.show', $patient) }}" class="btn-secondary" target="_blank"><x-icon name="file" class="h-4 w-4" /> Full record</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('consultations.store', $visit) }}" class="space-y-6 xl:col-span-2" x-data="{ outcome: '{{ old('outcome', 'send_home') }}' }">
            @csrf
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Clinical notes</h2></div>
                <div class="panel-body grid gap-5">
                    <x-field.textarea name="history" label="History and presenting complaint" rows="4" required />
                    <x-field.textarea name="examination" label="Examination findings" rows="3" />
                    <x-field.input name="diagnosis" label="Diagnosis" required placeholder="For example, uncomplicated malaria" />
                    <x-field.textarea name="treatment_plan" label="Treatment plan and advice" rows="3" />
                </div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Prescription</h2><span class="text-[13px] text-muted">Leave empty if no medicine is needed</span></div>
                @include('partials.prescription-items')
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Outcome</h2></div>
                <div class="panel-body space-y-4">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 border p-3" :class="outcome === 'send_home' ? 'border-brand-700 bg-brand-50' : 'border-line'">
                            <input type="radio" name="outcome" value="send_home" x-model="outcome" class="mt-1 text-brand-700 focus:ring-brand-600">
                            <span><span class="block font-medium">Outpatient</span><span class="text-[13px] text-muted">Send to the pharmacy if medicine was prescribed, otherwise discharge</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 border p-3" :class="outcome === 'admit' ? 'border-brand-700 bg-brand-50' : 'border-line'">
                            <input type="radio" name="outcome" value="admit" x-model="outcome" class="mt-1 text-brand-700 focus:ring-brand-600">
                            <span><span class="block font-medium">Admit to a ward</span><span class="text-[13px] text-muted">The ward team will allocate a bed</span></span>
                        </label>
                    </div>
                    @error('outcome')<p class="field-error">{{ $message }}</p>@enderror
                    <div x-show="outcome === 'admit'" x-cloak class="grid gap-5 sm:grid-cols-2">
                        <x-field.textarea name="admission_reason" label="Reason for admission" rows="2" class="sm:col-span-2" />
                        <x-field.select name="preferred_ward_type" label="Preferred ward type" :options="$wardTypes" placeholder="Any suitable ward" />
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <a href="{{ route('visits.queue') }}" class="btn-secondary">Back to queue</a>
                <button type="submit" class="btn-primary">Complete consultation</button>
            </div>
        </form>

        <aside class="space-y-6">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Patient</h2></div>
                <dl class="panel-body detail-list">
                    <div><dt>Passport number</dt><dd class="mono">{{ $patient->passport_number }}</dd></div>
                    <div><dt>Age and sex</dt><dd>{{ $patient->age_label }}, {{ $patient->sex->label() }}</dd></div>
                    <div><dt>Blood group</dt><dd>{{ $patient->blood_group ?? 'Not known' }}</dd></div>
                    <div><dt>Allergies</dt><dd class="{{ $patient->allergies ? 'font-medium text-red-700' : '' }}">{{ $patient->allergies ?? 'None recorded' }}</dd></div>
                    <div class="sm:col-span-2"><dt>Long term conditions</dt><dd>{{ $patient->chronic_conditions ?? 'None recorded' }}</dd></div>
                </dl>
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Vital signs today</h2></div>
                @if ($vital)
                    <dl class="panel-body detail-list">
                        <div><dt>Temperature</dt><dd>{{ $vital->temperature }} °C</dd></div>
                        <div><dt>Weight</dt><dd>{{ $vital->weight }} kg</dd></div>
                        <div><dt>Blood pressure</dt><dd>{{ $vital->bloodPressure() ?? 'Not taken' }}</dd></div>
                        <div><dt>Pulse</dt><dd>{{ $vital->pulse_rate ? $vital->pulse_rate.' per minute' : 'Not taken' }}</dd></div>
                        <div><dt>Oxygen saturation</dt><dd>{{ $vital->oxygen_saturation ? $vital->oxygen_saturation.'%' : 'Not taken' }}</dd></div>
                        <div><dt>Breathing rate</dt><dd>{{ $vital->respiratory_rate ? $vital->respiratory_rate.' per minute' : 'Not taken' }}</dd></div>
                        @if ($vital->notes)<div class="sm:col-span-2"><dt>Nurse notes</dt><dd>{{ $vital->notes }}</dd></div>@endif
                    </dl>
                @else
                    <p class="px-5 py-4 text-sm text-gold-700">Vital signs have not been recorded for this visit.</p>
                @endif
            </section>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Previous diagnoses</h2></div>
                @forelse ($history as $past)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <p class="font-medium">{{ $past->diagnosis }}</p>
                        <p class="text-[13px] text-muted">{{ $past->checked_in_at->format('j M Y') }}, {{ $past->facility->name }}{{ $past->doctor ? ', '.$past->doctor->name : '' }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">No previous diagnoses.</p>
                @endforelse
            </section>
        </aside>
    </div>
</x-layouts.app>
