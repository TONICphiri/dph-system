<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Edit Patient: ") . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route("patients.update", $patient) }}" class="space-y-6">
                        @csrf
                        @method("PATCH")

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                            <p class="text-sm font-mono text-blue-900">
                                <strong>DHP ID:</strong> {{ $patient->dhp_id }}<br>
                                <strong>National ID:</strong> {{ $patient->national_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="first_name" :value="__("First Name")" />
                                <x-text-input id="first_name" name="first_name" type="text" 
                                             :value="old(\"first_name\", $patient->first_name)" 
                                             class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"first_name")" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="last_name" :value="__("Last Name")" />
                                <x-text-input id="last_name" name="last_name" type="text" 
                                             :value="old(\"last_name\", $patient->last_name)" 
                                             class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"last_name")" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="date_of_birth" :value="__("Date of Birth")" />
                                <x-text-input id="date_of_birth" name="date_of_birth" type="date" 
                                             :value="old(\"date_of_birth\", $patient->date_of_birth?->format(\"Y-m-d\"))" 
                                             class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"date_of_birth")" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="gender" :value="__("Gender")" />
                                <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">-- Select Gender --</option>
                                    <option value="M" {{ old("gender", $patient->gender) === "M" ? "selected" : "" }}>Male</option>
                                    <option value="F" {{ old("gender", $patient->gender) === "F" ? "selected" : "" }}>Female</option>
                                    <option value="Other" {{ old("gender", $patient->gender) === "Other" ? "selected" : "" }}>Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="phone_number" :value="__("Phone Number")" />
                            <x-text-input id="phone_number" name="phone_number" type="tel" 
                                         :value="old(\"phone_number\", $patient->phone_number)" 
                                         class="mt-1 block w-full" />
                        </div>

                        <div>
                            <x-input-label for="address" :value="__("Address")" />
                            <textarea id="address" name="address" rows="3" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old("address", $patient->address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="village" :value="__("Village")" />
                                <x-text-input id="village" name="village" type="text" 
                                             :value="old(\"village\", $patient->village)" 
                                             class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="district" :value="__("District")" />
                                <x-text-input id="district" name="district" type="text" 
                                             :value="old(\"district\", $patient->district)" 
                                             class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="status" :value="__("Status")" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="active" {{ old("status", $patient->status) === "active" ? "selected" : "" }}>Active</option>
                                <option value="inactive" {{ old("status", $patient->status) === "inactive" ? "selected" : "" }}>Inactive</option>
                                <option value="deceased" {{ old("status", $patient->status) === "deceased" ? "selected" : "" }}>Deceased</option>
                            </select>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <x-primary-button>{{ __("Update Patient") }}</x-primary-button>
                            <a href="{{ route("patients.show", $patient) }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
