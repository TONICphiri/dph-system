@php
    $editing = $patient->exists;
    $contacts = old('contacts', $editing
        ? $patient->emergencyContacts->map->only(['full_name', 'relationship', 'phone', 'physical_address'])->values()->all()
        : []);
    $isChild = (bool) old('is_child', $editing ? $patient->isChild() : $mother !== null);
    $isProvisional = $editing && $patient->isProvisional();
    $emergencyChild = (bool) old('emergency_child', $isProvisional && $isChild);
@endphp
<x-layouts.app :title="$editing ? 'Edit details' : 'Issue a passport'">
    <x-page-header :title="$editing ? 'Edit '.$patient->full_name : 'Issue a passport'">
        <x-slot:breadcrumb>
            @if ($editing)
                <a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">Passport</a>
            @else
                <a href="{{ route('patients.scan') }}" class="hover:text-brand-700">Open a passport</a>
            @endif
            <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'Issue' }}
        </x-slot:breadcrumb>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('patients.update', $patient) : route('patients.store') }}" class="space-y-6"
        x-data="{ isChild: {{ $isChild ? 'true' : 'false' }}, emergencyChild: {{ $emergencyChild ? 'true' : 'false' }}, emergencyReason: @js(old('emergency_child_reason', $editing ? $patient->provisional_reason : '')) }">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Identity --}}
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Identity</h2></div>
            <div class="panel-body space-y-5">
                @if ($editing && $isProvisional)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
                        <p class="font-medium text-amber-900">Provisional passport — mother not available at registration</p>
                        <p class="mt-1 text-amber-800">Complete identity by linking the mother and confirming sex and date of birth. The portal account cannot be created until this record is verified.</p>
                        <p class="mt-2 text-amber-800">Guardian: {{ $patient->guardian_name }}@if($patient->guardian_relationship) ({{ $patient->guardian_relationship }})@endif @if($patient->guardian_phone) — {{ $patient->guardian_phone }}@endif</p>
                        <p class="mt-1 text-amber-800">Reason: {{ $patient->provisional_reason_label }}</p>
                        @if ($patient->dob_is_estimated)
                            <p class="mt-1 text-amber-800">Date of birth is marked as estimated.</p>
                        @endif
                        @if ($patient->sex === \App\Enums\Sex::Unknown)
                            <p class="mt-1 text-amber-800">Sex is unknown and must be confirmed.</p>
                        @endif
                        @if ($patient->isChild() && ! $patient->mother_id)
                            <p class="mt-1 text-amber-800">Mother has not yet been linked.</p>
                        @endif
                    </div>
                @endif
                @if ($editing && $isChild)
                    <div class="border border-line bg-paper p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="label mb-0">Mother</p>
                            @if ($isProvisional)
                                <x-badge tone="warning">Provisional identity</x-badge>
                            @endif
                        </div>

                        @if ($patient->mother)
                            <div class="mt-2 border border-brand-200 bg-white px-3 py-2">
                                <p class="font-medium">{{ $patient->mother->full_name }}</p>
                                <p class="mono text-muted">{{ $patient->mother->passport_number }}</p>
                            </div>
                        @else
                            <div class="mt-2 space-y-2">
                                <p class="text-sm text-muted">The mother is not linked yet.</p>
                                @can('open', App\Models\Patient::class)
                                    <a href="{{ route('patients.scan', ['link_mother' => $patient->id]) }}" class="btn-secondary"><x-icon name="qr" class="h-4 w-4" /> Scan the mother's passport to link</a>
                                @endcan
                            </div>
                        @endif
                        @error('mother_id')<p class="field-error">{{ $message }}</p>@enderror

                        @if ($isProvisional)
                            <div class="mt-4 border-t border-line pt-4">
                                @include('partials.emergency-child-fields')
                            </div>
                        @endif
                    </div>
                @endif

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
                                <span><span class="block font-medium">Child under {{ $separationAge }}</span><span class="text-[13px] text-muted">Linked to the mother, or registered as an emergency</span></span>
                            </label>
                        </div>
                    </fieldset>

                    {{-- A child is linked to the mother's passport, or registered as an emergency when she is not available. --}}
                    <div x-show="isChild" x-cloak class="space-y-4 border border-line bg-paper p-4">
                        <label class="flex items-start gap-2.5 text-sm">
                            <input type="hidden" name="emergency_child" value="0">
                            <input type="checkbox" name="emergency_child" value="1" x-model="emergencyChild" @checked($emergencyChild) class="mt-0.5 h-4 w-4 border-line text-brand-700 focus:ring-brand-600">
                            <span>
                                <span class="block font-medium text-ink">Mother not available — register as an emergency</span>
                                <span class="block text-[13px] text-muted">The child gets a provisional passport and the mother can be linked later.</span>
                            </span>
                        </label>

                        <div x-show="!emergencyChild" x-cloak>
                            <p class="label">Mother</p>
                            @if ($mother)
                                <input type="hidden" name="mother_id" value="{{ $mother->id }}" :disabled="!isChild || emergencyChild">
                                <div class="border border-brand-200 bg-white px-3 py-2">
                                    <p class="font-medium">{{ $mother->full_name }}</p>
                                    <p class="mono text-muted">{{ $mother->passport_number }}</p>
                                </div>
                            @else
                                <a href="{{ route('patients.scan') }}" class="btn-secondary"><x-icon name="qr" class="h-4 w-4" /> Scan the mother's passport</a>
                            @endif
                            @error('mother_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="emergencyChild" x-cloak class="border-t border-line pt-4">
                            @include('partials.emergency-child-fields')
                        </div>
                    </div>
                @endunless

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- A child has no National ID; hide the field while "Child" is selected. --}}
                    <div x-show="!isChild" x-cloak>
                        <x-field.input name="national_id" label="National ID" :value="$patient->national_id" :maxlength="$nationalIdLength" :placeholder="str_repeat('X', $nationalIdLength)" x-bind:disabled="isChild" />
                    </div>
                    <x-field.input name="first_name" label="First name" :value="$patient->first_name" required />
                    <x-field.input name="middle_name" label="Middle name" :value="$patient->middle_name" />
                    <x-field.input name="last_name" label="Surname" :value="$patient->last_name" required />
                    <x-field.input name="date_of_birth" label="Date of birth" type="date" :value="$patient->date_of_birth?->toDateString()" :max="now()->toDateString()" required />
                    <x-field.select name="sex" label="Sex" :options="$sexes" :value="$patient->sex" required :help="$isProvisional ? 'Unknown is allowed while the record is provisional. Confirm the sex to complete the identity.' : null" />
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
                <x-field.input name="email" label="Email address" type="email" :value="$patient->email" />
                <x-field.checkbox name="sms_consent" label="Send reminders by text message" help="Only a short prompt is sent, never clinical details." :checked="$patient->sms_consent" class="sm:col-span-2 lg:col-span-3" />
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
            <p x-show="emergencyChild" x-cloak class="border-b border-line bg-paper px-5 py-2 text-[13px] text-muted">Optional in an emergency — the guardian's details are saved as the first contact.</p>
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid gap-4 border-b border-line px-5 py-4 last:border-b-0 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.4fr_auto]">
                    <div><label class="label" :for="'contact_name_' + index">Full name <span x-show="index === 0 && !emergencyChild" class="text-red-700">*</span></label><input :id="'contact_name_' + index" class="input" :name="'contacts[' + index + '][full_name]'" x-model="row.full_name"></div>
                    <div>
                        <label class="label" :for="'contact_relationship_' + index">Relationship</label>
                        <select :id="'contact_relationship_' + index" class="input" :name="'contacts[' + index + '][relationship]'" x-model="row.relationship">
                            <option value="">Choose</option>
                            @foreach ($relationships as $relationship)
                                <option value="{{ $relationship }}">{{ $relationship }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" :for="'contact_phone_' + index">Phone <span x-show="index === 0 && !emergencyChild" class="text-red-700">*</span></label><input :id="'contact_phone_' + index" class="input" :name="'contacts[' + index + '][phone]'" x-model="row.phone"></div>
                    <div><label class="label" :for="'contact_address_' + index">Address</label><input :id="'contact_address_' + index" class="input" :name="'contacts[' + index + '][physical_address]'" x-model="row.physical_address"></div>
                    <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove contact"><x-icon name="x" class="h-4 w-4" /></button></div>
                </div>
            </template>
        </section>

        @unless ($editing)
            <section class="panel" x-show="!emergencyChild" x-cloak>
                <div class="panel-body">
                    <x-field.checkbox name="create_portal_account" label="Create a portal account for the passport holder" />
                </div>
            </section>
        @endunless

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('patients.show', $patient) : route('patients.scan') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Issue passport' }}</button>
        </div>
    </form>
</x-layouts.app>
