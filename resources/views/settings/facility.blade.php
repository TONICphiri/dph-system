<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Facility administration · Profile</p>
            <h2 class="text-2xl font-extrabold leading-tight">{{ $facility->name }}</h2>
            <p class="text-sm text-dhp-100">Shown on the public sign-in page — changes appear immediately.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="dhp-card dhp-card-pad">
            @if($facilities->count())
                <form method="GET" action="{{ route('settings.facility.edit') }}" class="dhp-alert-info mb-6">
                    <label for="facility" class="text-sm font-bold whitespace-nowrap">Editing facility</label>
                    <select id="facility" name="facility" onchange="this.form.submit()" class="dhp-input flex-1">
                        @foreach($facilities as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $facility->id)>{{ $option->name }} ({{ $option->district }})</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <form method="POST" action="{{ route('settings.facility.update', request()->only('facility')) }}" enctype="multipart/form-data" class="space-y-5" novalidate>
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="dhp-label">Hospital name <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $facility->name) }}" required maxlength="255" class="dhp-input" />
                        @error('name')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="facility_type" class="dhp-label">Facility type <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <select id="facility_type" name="facility_type" required class="dhp-select">
                            @foreach(['Hospital', 'Health Centre', 'Clinic'] as $type)
                                <option value="{{ $type }}" @selected(old('facility_type', $facility->facility_type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('facility_type')<p class="dhp-field-error">{{ $message }}</p>@enderror
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
                        <label for="secondary_phone" class="dhp-label">Secondary phone</label>
                        <input type="text" id="secondary_phone" name="secondary_phone" value="{{ old('secondary_phone', $facility->secondary_phone) }}" maxlength="20" class="dhp-input" />
                    </div>
                    <div>
                        <label for="email" class="dhp-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $facility->email) }}" maxlength="255" class="dhp-input" />
                        @error('email')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="website" class="dhp-label">Website</label>
                        <input type="text" id="website" name="website" value="{{ old('website', $facility->website) }}" maxlength="255" class="dhp-input" />
                    </div>
                    <div>
                        <label for="working_hours" class="dhp-label">Working hours</label>
                        <input type="text" id="working_hours" name="working_hours" value="{{ old('working_hours', $facility->working_hours) }}" placeholder="Mon–Fri 07:30–17:00 · Ward 24 hrs" maxlength="255" class="dhp-input" />
                    </div>
                    <div>
                        <label for="map_url" class="dhp-label">Map link (URL)</label>
                        <input type="url" id="map_url" name="map_url" value="{{ old('map_url', $facility->map_url) }}" placeholder="https://…" maxlength="500" class="dhp-input" />
                        @error('map_url')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="logo" class="dhp-label">Logo</label>
                    @can('manage_global_settings')
                        @if($facility->logo_path)
                            <p class="dhp-help mb-1">Current: {{ $facility->logo_path }}</p>
                        @endif
                        <input type="file" id="logo" name="logo" accept="image/*" class="dhp-input" />
                        @error('logo')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    @else
                        <p class="text-sm text-slate-500">Only the national admin can change the facility logo. Contact the main administrator to update it.</p>
                    @endcan
                </div>
                <div>
                    <label for="address" class="dhp-label">Address <span class="font-normal text-slate-500">(shown in the footer)</span></label>
                    <textarea id="address" name="address" rows="2" maxlength="500" class="dhp-input">{{ old('address', $facility->address) }}</textarea>
                </div>
                <div>
                    <label for="services" class="dhp-label">Services <span class="font-normal text-slate-500">(one per line)</span></label>
                    <textarea id="services" name="services" rows="5" class="dhp-input dhp-mono !text-[13px]">{{ old('services', is_array($facility->services) ? implode("\n", $facility->services) : '') }}</textarea>
                </div>
                <div>
                    <label for="departments" class="dhp-label">Departments <span class="font-normal text-slate-500">(one per line)</span></label>
                    <textarea id="departments" name="departments" rows="5" class="dhp-input dhp-mono !text-[13px]">{{ old('departments', is_array($facility->departments) ? implode("\n", $facility->departments) : '') }}</textarea>
                </div>
                <button type="submit" class="btn-primary w-full sm:w-auto">Save settings</button>
            </form>
        </div>
    </div>
</x-app-layout>
