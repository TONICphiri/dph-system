<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Inpatient · Admission</p>
            <h2 class="text-2xl font-extrabold leading-tight">Admit {{ $patient->full_name }}</h2>
            <p class="text-sm text-dhp-100 dhp-mono !text-dhp-100">{{ $patient->dhp_id }} · {{ $patient->national_id ?? 'No National ID' }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Bed assignment</h3>
            <p class="dhp-section-sub">One active admission per patient. Beds are checked for occupancy before assignment.</p>

            <form action="{{ route('admission.create') }}" method="POST" class="mt-5 space-y-5" novalidate>
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ward" class="dhp-label">Ward <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="ward" name="ward" value="{{ old('ward') }}" required maxlength="100" placeholder="e.g. General Ward, Pediatrics" class="dhp-input" />
                        @error('ward')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="bed_number" class="dhp-label">Bed number <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="bed_number" name="bed_number" value="{{ old('bed_number') }}" required maxlength="20" placeholder="e.g. A-105" class="dhp-input" />
                        @error('bed_number')<p class="dhp-field-error">{{ $message }}</p>@enderror
                        <p class="dhp-help">Occupied beds are rejected automatically.</p>
                    </div>
                </div>

                <div>
                    <label for="admission_type" class="dhp-label">Admission type <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select id="admission_type" name="admission_type" required class="dhp-select">
                        <option value="">Select admission type…</option>
                        @foreach(['emergency' => 'Emergency', 'urgent' => 'Urgent', 'elective' => 'Elective', 'transfer' => 'Transfer'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('admission_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('admission_type')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="admission_reason" class="dhp-label">Reason for admission</label>
                    <textarea id="admission_reason" name="admission_reason" rows="3" maxlength="2000" placeholder="Presenting condition and reason for inpatient care…" class="dhp-input">{{ old('admission_reason') }}</textarea>
                    @error('admission_reason')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-warning flex-1" onclick="return confirm('Admit {{ addslashes($patient->full_name) }} to inpatient care?');">Admit patient</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn-secondary flex-1">Back to patient</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
