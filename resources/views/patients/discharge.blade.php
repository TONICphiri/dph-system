<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Inpatient · Discharge</p>
            <h2 class="text-2xl font-extrabold leading-tight">Discharge {{ $patient->full_name }}</h2>
            <p class="text-sm text-dhp-100">{{ $activeAdmission->ward_name ?? 'Ward' }}, Bed {{ $activeAdmission->bed_number ?? '—' }} · Admitted {{ $activeAdmission->admitted_at->format('d M Y') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Discharge summary</h3>
            <p class="dhp-section-sub">This closes the active admission and frees the bed. All fields become part of the lifelong record.</p>

            <form action="{{ route('discharge.store', $patient) }}" method="POST" class="mt-5 space-y-5" novalidate>
                @csrf
                <div>
                    <label for="final_diagnosis" class="dhp-label">Final diagnosis <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <textarea id="final_diagnosis" name="final_diagnosis" rows="3" required maxlength="2000" placeholder="e.g. Pneumonia — resolved with antibiotics" class="dhp-input">{{ old('final_diagnosis') }}</textarea>
                    @error('final_diagnosis')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="discharge_status" class="dhp-label">Outcome</label>
                        <select id="discharge_status" name="discharge_status" class="dhp-select">
                            @foreach(['Improved', 'Not Improved', 'Referred', 'Left Against Medical Advice', 'Deceased'] as $s)
                                <option value="{{ $s }}" @selected(old('discharge_status', 'Improved') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        @error('discharge_status')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="discharge_date" class="dhp-label">Discharge date</label>
                        <input type="date" id="discharge_date" name="discharge_date" value="{{ old('discharge_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="dhp-input" />
                        @error('discharge_date')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="follow_up_instructions" class="dhp-label">Follow-up instructions</label>
                    <textarea id="follow_up_instructions" name="follow_up_instructions" rows="3" maxlength="2000" placeholder="e.g. Return in 2 weeks for review; continue medication…" class="dhp-input">{{ old('follow_up_instructions') }}</textarea>
                    @error('follow_up_instructions')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-success flex-1" onclick="return confirm('Discharge {{ addslashes($patient->full_name) }}? The bed will be freed.');">Discharge patient</button>
                    <a href="{{ route('ward', $patient) }}" class="btn-secondary flex-1">Back to ward</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
