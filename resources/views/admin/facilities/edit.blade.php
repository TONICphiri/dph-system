<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Hospitals</p>
            <h2 class="text-2xl font-extrabold leading-tight">Edit {{ $facility->name }}</h2>
            <p class="dhp-mono !text-dhp-100">{{ $facility->facility_code }} · {{ $facility->district }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="dhp-card dhp-card-pad">
            <form method="POST" action="{{ route('facilities.update', $facility) }}" class="space-y-5" novalidate>
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="dhp-label">Facility name <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $facility->name) }}" required maxlength="255" class="dhp-input" />
                        @error('name')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="facility_code" class="dhp-label">Facility code <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="facility_code" name="facility_code" value="{{ old('facility_code', $facility->facility_code) }}" required maxlength="50" class="dhp-input" />
                        @error('facility_code')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="facility_type" class="dhp-label">Facility type <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <select id="facility_type" name="facility_type" required class="dhp-select">
                            @foreach(['Health Centre', 'Hospital', 'Clinic'] as $type)
                                <option value="{{ $type }}" @selected(old('facility_type', $facility->facility_type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('facility_type')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="status" class="dhp-label">Status</label>
                        <select id="status" name="status" class="dhp-select">
                            <option value="active" @selected(old('status', $facility->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $facility->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="district" class="dhp-label">District <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="district" name="district" value="{{ old('district', $facility->district) }}" required maxlength="100" class="dhp-input" />
                        @error('district')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="region" class="dhp-label">Region</label>
                        <input type="text" id="region" name="region" value="{{ old('region', $facility->region) }}" maxlength="100" class="dhp-input" />
                    </div>
                    <div>
                        <label for="phone_number" class="dhp-label">Phone number</label>
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $facility->phone_number) }}" maxlength="20" class="dhp-input" />
                    </div>
                    <div>
                        <label for="email" class="dhp-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $facility->email) }}" maxlength="255" class="dhp-input" />
                        @error('email')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="address" class="dhp-label">Address</label>
                    <textarea id="address" name="address" rows="2" maxlength="500" class="dhp-input">{{ old('address', $facility->address) }}</textarea>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-primary flex-1">Update facility</button>
                    <a href="{{ route('facilities.index') }}" class="btn-secondary flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
