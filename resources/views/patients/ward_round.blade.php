<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Ward round · Observations</p>
            <h2 class="text-2xl font-extrabold leading-tight">{{ $patient->full_name }}</h2>
            <p class="text-sm text-dhp-100">{{ $activeAdmission->ward_name ?? 'Ward' }}, Bed {{ $activeAdmission->bed_number ?? '—' }} · A new vital row is appended each round — history is never overwritten.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Round observations</h3>
            <p class="dhp-section-sub">Enter at least one vital sign or note. Priority is calculated automatically.</p>

            <form action="{{ route('ward.round') }}" method="POST" class="mt-5 space-y-5" novalidate>
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="temperature" class="dhp-label">Temperature (°C)</label>
                        <input type="number" step="0.1" min="30" max="45" id="temperature" name="temperature" value="{{ old('temperature') }}" placeholder="e.g. 36.8" class="dhp-input" />
                        @error('temperature')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="heart_rate" class="dhp-label">Heart rate (bpm)</label>
                        <input type="number" min="30" max="250" id="heart_rate" name="heart_rate" value="{{ old('heart_rate') }}" placeholder="e.g. 80" class="dhp-input" />
                        @error('heart_rate')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="respiratory_rate" class="dhp-label">Respiratory rate (/min)</label>
                        <input type="number" min="5" max="80" id="respiratory_rate" name="respiratory_rate" value="{{ old('respiratory_rate') }}" placeholder="e.g. 16" class="dhp-input" />
                        @error('respiratory_rate')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="oxygen_saturation" class="dhp-label">Oxygen saturation (%)</label>
                        <input type="number" min="50" max="100" id="oxygen_saturation" name="oxygen_saturation" value="{{ old('oxygen_saturation') }}" placeholder="e.g. 98" class="dhp-input" />
                        @error('oxygen_saturation')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="blood_pressure_systolic" class="dhp-label">BP systolic (mmHg)</label>
                        <input type="number" min="50" max="250" id="blood_pressure_systolic" name="blood_pressure_systolic" value="{{ old('blood_pressure_systolic') }}" placeholder="e.g. 120" class="dhp-input" />
                    </div>
                    <div>
                        <label for="blood_pressure_diastolic" class="dhp-label">BP diastolic (mmHg)</label>
                        <input type="number" min="30" max="200" id="blood_pressure_diastolic" name="blood_pressure_diastolic" value="{{ old('blood_pressure_diastolic') }}" placeholder="e.g. 80" class="dhp-input" />
                    </div>
                    <div>
                        <label for="weight" class="dhp-label">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" max="400" id="weight" name="weight" value="{{ old('weight') }}" placeholder="e.g. 65.5" class="dhp-input" />
                        @error('weight')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="notes" class="dhp-label">Round notes</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="2000" placeholder="Observations, response, plan…" class="dhp-input">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-primary flex-1">Save round observations</button>
                    <a href="{{ route('ward', $patient) }}" class="btn-secondary flex-1">Back to ward</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
