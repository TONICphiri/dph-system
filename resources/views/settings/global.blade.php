<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Configuration</p>
            <h2 class="text-2xl font-extrabold leading-tight">National passport configuration</h2>
            <p class="text-sm text-dhp-100">Applies to every registered hospital nationwide.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="dhp-card dhp-card-pad">
            <form method="POST" action="{{ route('settings.global.update') }}" class="space-y-5" novalidate>
                @csrf
                @method('PUT')
                <div>
                    <label for="display_facility_id" class="dhp-label">Display facility <span class="font-normal text-slate-500">(public login page shows this hospital)</span></label>
                    <select id="display_facility_id" name="display_facility_id" class="dhp-select">
                        <option value="">— First active facility —</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected((string) old('display_facility_id', $values['display_facility_id'] ?? '') === (string) $facility->id)>
                                {{ $facility->name }} ({{ $facility->district }})
                            </option>
                        @endforeach
                    </select>
                    @error('display_facility_id')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="passport_fields" class="dhp-label">Standardized passport medical fields <span class="font-normal text-slate-500">(one per line)</span></label>
                    <textarea id="passport_fields" name="passport_fields" rows="6" class="dhp-input dhp-mono !text-[13px]" placeholder="e.g. Allergies&#10;Chronic conditions&#10;Blood group">{{ old('passport_fields', $values['passport_fields'] ?? '') }}</textarea>
                    @error('passport_fields')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="vaccine_categories" class="dhp-label">Vaccine categories <span class="font-normal text-slate-500">(one per line)</span></label>
                    <textarea id="vaccine_categories" name="vaccine_categories" rows="6" class="dhp-input dhp-mono !text-[13px]">{{ old('vaccine_categories', $values['vaccine_categories'] ?? '') }}</textarea>
                    @error('vaccine_categories')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="health_templates" class="dhp-label">Global health data templates <span class="font-normal text-slate-500">(one per line)</span></label>
                    <textarea id="health_templates" name="health_templates" rows="6" class="dhp-input dhp-mono !text-[13px]">{{ old('health_templates', $values['health_templates'] ?? '') }}</textarea>
                    @error('health_templates')<p class="dhp-field-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary w-full sm:w-auto">Save configuration</button>
            </form>
        </div>
    </div>
</x-app-layout>
