@php
    $editing = $patient->exists;
    $contacts = old('contacts', $editing
        ? $patient->emergencyContacts->map->only(['full_name', 'relationship', 'phone', 'physical_address'])->values()->all()
        : []);
    $motherData = $mother ? ['id' => $mother->id, 'name' => $mother->full_name, 'passport_number' => $mother->passport_number, 'national_id' => $mother->national_id] : null;
    $isChild = (bool) old('is_child', $mother !== null);
@endphp
<x-layouts.app :title="$editing ? 'Edit patient' : 'Register patient'">
    <x-page-header :title="$editing ? 'Edit '.$patient->full_name : 'Register a patient'"
        :description="$editing ? 'Changes are recorded in the activity log.' : 'Search for the patient first to avoid duplicate records. Adults are registered with their National ID. Children under '.$separationAge.' are linked to their mother.'">
        <x-slot:breadcrumb><a href="{{ route('patients.index') }}" class="hover:text-brand-700">Patients</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'Register' }}</x-slot:breadcrumb>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('patients.update', $patient) : route('patients.store') }}" class="space-y-6"
        x-data="{ isChild: {{ $isChild ? 'true' : 'false' }} }">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Identity --}}
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Identity</h2><span class="text-[13px] text-muted">Fields marked * are required</span></div>
            <div class="panel-body space-y-5">
                @unless ($editing)
                    <fieldset>
                        <legend class="label">Type of registration</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 border p-3" :class="!isChild ? 'border-brand-700 bg-brand-50' : 'border-line'">
                                <input type="radio" name="is_child" value="0" @change="isChild = false" class="mt-1 text-brand-700 focus:ring-brand-600" @checked(! $isChild)>
                                <span><span class="block font-medium">Adult</span><span class="text-[13px] text-muted">Registered with a National ID</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 border p-3" :class="isChild ? 'border-brand-700 bg-brand-50' : 'border-line'">
                                <input type="radio" name="is_child" value="1" @change="isChild = true" class="mt-1 text-brand-700 focus:ring-brand-600" @checked($isChild)>
                                <span><span class="block font-medium">Child under {{ $separationAge }}</span><span class="text-[13px] text-muted">Linked to the mother's record</span></span>
                            </label>
                        </div>
                    </fieldset>

                    {{-- Mother search, shown for children --}}
                    <div x-show="isChild" x-cloak x-data="motherSearch('{{ route('patients.mothers') }}', @js($motherData))" class="border border-line bg-paper p-4">
                        <p class="label">Mother</p>
                        <input type="hidden" name="mother_id" :value="selected ? selected.id : ''" :disabled="!isChild">
                        <template x-if="selected">
                            <div class="flex items-center justify-between border border-brand-200 bg-white px-3 py-2">
                                <div><p class="font-medium" x-text="selected.name"></p><p class="mono text-muted" x-text="selected.passport_number"></p></div>
                                <button type="button" class="link text-sm" @click="clear()">Change</button>
                            </div>
                        </template>
                        <div x-show="!selected">
                            <input type="search" class="input" placeholder="Search the mother by name, National ID or passport number" x-model="term" @input.debounce.400ms="search()" aria-label="Search for the mother">
                            <p x-show="loading" class="field-help">Searching.</p>
                            <p x-show="failed" class="field-error">The search could not be completed. Please try again.</p>
                            <ul x-show="results.length" class="mt-1 border border-line bg-white">
                                <template x-for="mother in results" :key="mother.id">
                                    <li><button type="button" class="block w-full border-b border-line px-3 py-2 text-left last:border-b-0 hover:bg-brand-50" @click="choose(mother)">
                                        <span class="font-medium" x-text="mother.name"></span> <span class="mono text-muted" x-text="mother.passport_number"></span>
                                    </button></li>
                                </template>
                            </ul>
                            <p class="field-help">The mother must already be registered. Register her first if she is not found.</p>
                        </div>
                        @error('mother_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endunless

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <x-field.input name="national_id" label="National ID" :value="$patient->national_id" maxlength="8" help="Required for adults. 8 letters and numbers, as printed on the National ID card." />
                    <x-field.input name="first_name" label="First name" :value="$patient->first_name" required />
                    <x-field.input name="middle_name" label="Middle name" :value="$patient->middle_name" />
                    <x-field.input name="last_name" label="Surname" :value="$patient->last_name" required />
                    <x-field.input name="date_of_birth" label="Date of birth" type="date" :value="$patient->date_of_birth?->toDateString()" :max="now()->toDateString()" required />
                    <x-field.select name="sex" label="Sex" :options="$sexes" :value="$patient->sex" required />
                    @if ($editing)
                        <x-field.select name="status" label="Record status" :options="$statuses" :value="$patient->status" :placeholder="false" required />
                    @endif
                </div>
            </div>
        </section>

        {{-- Contact and address --}}
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Contact and address</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-field.input name="phone" label="Phone number" :value="$patient->phone" placeholder="0999 123 456" />
                <x-field.input name="email" label="Email address" type="email" :value="$patient->email" help="Needed for a patient portal account." />
                <x-field.input name="occupation" label="Occupation" :value="$patient->occupation" />
                <x-field.select name="district_id" label="District" :options="$districts->pluck('name', 'id')->all()" :value="$patient->district_id" required />
                <x-field.input name="traditional_authority" label="Traditional Authority" :value="$patient->traditional_authority" />
                <x-field.input name="village" label="Village" :value="$patient->village" />
                <x-field.input name="physical_address" label="Physical address" :value="$patient->physical_address" class="sm:col-span-2 lg:col-span-3" />
            </div>
        </section>

        {{-- Initial health information --}}
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Initial health information</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.select name="blood_group" label="Blood group" :options="$bloodGroups" :value="$patient->blood_group" placeholder="Not known" />
                <div class="hidden sm:block"></div>
                <x-field.textarea name="allergies" label="Allergies" :value="$patient->allergies" rows="2" placeholder="For example, penicillin" />
                <x-field.textarea name="chronic_conditions" label="Long term conditions" :value="$patient->chronic_conditions" rows="2" placeholder="For example, diabetes, hypertension" />
                <x-field.textarea name="disabilities" label="Disabilities" :value="$patient->disabilities" rows="2" />
                <x-field.textarea name="health_notes" label="Other notes" :value="$patient->health_notes" rows="2" />
            </div>
        </section>

        {{-- Emergency contacts --}}
        <section class="panel" x-data="repeater(@js($contacts), { full_name: '', relationship: '', phone: '', physical_address: '' }, 3)">
            <div class="panel-header">
                <h2 class="panel-title">Emergency contacts</h2>
                <button type="button" class="btn-secondary btn-sm" @click="add()" :disabled="rows.length >= 3"><x-icon name="plus" class="h-4 w-4" /> Add contact</button>
            </div>
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid gap-4 border-b border-line px-5 py-4 last:border-b-0 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.4fr_auto]">
                    <div><label class="label" :for="'contact_name_' + index">Full name <span x-show="index === 0" class="text-red-700">*</span></label><input :id="'contact_name_' + index" class="input" :name="'contacts[' + index + '][full_name]'" x-model="row.full_name"></div>
                    <div>
                        <label class="label" :for="'contact_relationship_' + index">Relationship</label>
                        <select :id="'contact_relationship_' + index" class="input" :name="'contacts[' + index + '][relationship]'" x-model="row.relationship">
                            <option value="">Choose</option>
                            @foreach ($relationships as $relationship)
                                <option value="{{ $relationship }}">{{ $relationship }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" :for="'contact_phone_' + index">Phone <span x-show="index === 0" class="text-red-700">*</span></label><input :id="'contact_phone_' + index" class="input" :name="'contacts[' + index + '][phone]'" x-model="row.phone"></div>
                    <div><label class="label" :for="'contact_address_' + index">Address</label><input :id="'contact_address_' + index" class="input" :name="'contacts[' + index + '][physical_address]'" x-model="row.physical_address"></div>
                    <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove contact"><x-icon name="x" class="h-4 w-4" /></button></div>
                </div>
            </template>
            <p class="border-t border-line px-5 py-2 text-[13px] text-muted">The first contact is the primary contact. Up to three contacts can be recorded.</p>
        </section>

        @unless ($editing)
            <section class="panel">
                <div class="panel-body">
                    <x-field.checkbox name="create_portal_account" label="Create a patient portal account" help="The patient can then book appointments and view records online. An email address is required. A one time password will be shown after saving." />
                </div>
            </section>
        @endunless

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('patients.show', $patient) : route('patients.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Register patient' }}</button>
        </div>
    </form>
</x-layouts.app>
