<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Register New Patient") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route("patients.store") }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="national_id" :value="__("National ID (Required)")" />
                            <x-text-input id="national_id" name="national_id" type="text" 
                                         placeholder="e.g., A123456789" required autofocus
                                         :value="old(\"national_id\")" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get(\"national_id")" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="first_name" :value="__("First Name")" />
                                <x-text-input id="first_name" name="first_name" type="text" 
                                             required :value="old(\"first_name\")" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"first_name")" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="last_name" :value="__("Last Name")" />
                                <x-text-input id="last_name" name="last_name" type="text" 
                                             required :value="old(\"last_name\")" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"last_name")" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="date_of_birth" :value="__("Date of Birth")" />
                                <x-text-input id="date_of_birth" name="date_of_birth" type="date" 
                                             :value="old(\"date_of_birth\")" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get(\"date_of_birth")" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="gender" :value="__("Gender")" />
                                <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">-- Select Gender --</option>
                                    <option value="M" {{ old("gender") === "M" ? "selected" : "" }}>Male</option>
                                    <option value="F" {{ old("gender") === "F" ? "selected" : "" }}>Female</option>
                                    <option value="Other" {{ old("gender") === "Other" ? "selected" : "" }}>Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="phone_number" :value="__("Phone Number")" />
                            <x-text-input id="phone_number" name="phone_number" type="tel" 
                                         placeholder="+265 1 XXX XXXX" :value="old(\"phone_number\")" 
                                         class="mt-1 block w-full" />
                        </div>

                        <div>
                            <x-input-label for="address" :value="__("Address")" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                      placeholder="Street address">{{ old("address") }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="village" :value="__("Village")" />
                                <x-text-input id="village" name="village" type="text" 
                                             :value="old(\"village\")" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="district" :value="__("District")" />
                                <x-text-input id="district" name="district" type="text" 
                                             :value="old(\"district\")" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input id="is_child" name="is_child" type="checkbox" value="1" 
                                   class="rounded" {{ old("is_child") ? "checked" : "" }} />
                            <label for="is_child" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                This is a child without a National ID
                            </label>
                        </div>

                        <div id="guardian-section" class="hidden">
                            <x-input-label for="guardian_id" :value="__("Guardian")" />
                            <select id="guardian_id" name="guardian_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Select Guardian --</option>
                                @foreach ($guardians as $guardian)
                                    <option value="{{ $guardian->id }}" {{ old("guardian_id") == $guardian->id ? "selected" : "" }}>
                                        {{ $guardian->full_name }} ({{ $guardian->relationship }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <x-primary-button>{{ __("Register Patient") }}</x-primary-button>
                            <a href="{{ route("patients.index") }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("is_child").addEventListener("change", function() {
            const guardianSection = document.getElementById("guardian-section");
            guardianSection.classList.toggle("hidden", !this.checked);
        });

        // Initialize guardian section visibility
        document.getElementById("guardian-section").classList.toggle("hidden", !document.getElementById("is_child").checked);
    </script>
</x-app-layout>
