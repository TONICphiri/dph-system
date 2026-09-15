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
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                        <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Check for an existing record first</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">Search by National ID before registering to avoid duplicate records.</p>
                        <div class="flex gap-2">
                            <input type="text" id="lookup-national-id" placeholder="Enter National ID, e.g. A123456789"
                                   class="flex-1 px-4 py-2 border rounded-md shadow-sm" />
                            <button type="button" id="lookup-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Look Up
                            </button>
                        </div>
                        <div id="lookup-result" class="mt-3"></div>
                    </div>

                    <form method="POST" action="{{ route("patients.store") }}" class="space-y-6">
                        @csrf

<div>
                            <x-input-label for="national_id" :value="__("National ID (required unless child)")" />
                            <x-text-input id="national_id" name="national_id" type="text"
                                         placeholder="e.g., A123456789" autofocus
                                         :value="old(\"national_id\")" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-gray-500">Enter all details below exactly as they appear on the National ID.</p>
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
                                <x-input-label for="village" :value="__("Home Village")" />
                                <x-text-input id="village" name="village" type="text"
                                             :value="old(\"village\")" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="traditional_authority" :value="__("Traditional Authority")" />
                                <x-text-input id="traditional_authority" name="traditional_authority" type="text"
                                             :value="old(\"traditional_authority\")" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="district" :value="__("District")" />
                            <select id="district" name="district" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Select District --</option>
                                @foreach (config("districts") as $name => $code)
                                    <option value="{{ $name }}" {{ old("district") === $name ? "selected" : "" }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Used to generate the patient's Health Passport ID.</p>
                            <x-input-error :messages="$errors->get(\"district")" class="mt-2" />
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
                                <option value="">-- Select Existing Guardian --</option>
                                @foreach ($guardians as $guardian)
                                    <option value="{{ $guardian->id }}" {{ old("guardian_id") == $guardian->id ? "selected" : "" }}>
                                        {{ $guardian->full_name }} ({{ $guardian->relationship }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2">
                                <label for="new-guardian-toggle" class="text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" id="new-guardian-toggle" class="rounded" {{ old("guardian_first_name") ? "checked" : "" }} />
                                    Register a new guardian
                                </label>
                            </div>

                            <div id="new-guardian-form" class="hidden mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg space-y-3">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="guardian_first_name" :value="__("Guardian First Name")" />
                                        <x-text-input id="guardian_first_name" name="guardian_first_name" type="text"
                                                     :value="old(\"guardian_first_name\")" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <x-input-label for="guardian_last_name" :value="__("Guardian Last Name")" />
                                        <x-text-input id="guardian_last_name" name="guardian_last_name" type="text"
                                                     :value="old(\"guardian_last_name\")" class="mt-1 block w-full" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="guardian_relationship" :value="__("Relationship to Child")" />
                                    <select id="guardian_relationship" name="guardian_relationship" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="Mother" {{ old("guardian_relationship") === "Mother" ? "selected" : "" }}>Mother</option>
                                        <option value="Father" {{ old("guardian_relationship") === "Father" ? "selected" : "" }}>Father</option>
                                        <option value="Grandparent" {{ old("guardian_relationship") === "Grandparent" ? "selected" : "" }}>Grandparent</option>
                                        <option value="Sibling" {{ old("guardian_relationship") === "Sibling" ? "selected" : "" }}>Sibling</option>
                                        <option value="Relative" {{ old("guardian_relationship") === "Relative" ? "selected" : "" }}>Relative</option>
                                        <option value="Other" {{ old("guardian_relationship") === "Other" ? "selected" : "" }}>Other</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="guardian_national_id" :value="__("Guardian National ID (optional)")" />
                                        <x-text-input id="guardian_national_id" name="guardian_national_id" type="text"
                                                     :value="old(\"guardian_national_id\")" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <x-input-label for="guardian_phone_number" :value="__("Guardian Phone")" />
                                        <x-text-input id="guardian_phone_number" name="guardian_phone_number" type="tel"
                                                     :value="old(\"guardian_phone_number\")" class="mt-1 block w-full" />
                                    </div>
                                </div>
                            </div>
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
        const isChild = document.getElementById("is_child");
        const guardianSection = document.getElementById("guardian-section");
        const newGuardianForm = document.getElementById("new-guardian-form");
        const newGuardianToggle = document.getElementById("new-guardian-toggle");
        const nationalIdInput = document.getElementById("national_id");

        function toggleGuardianVisibility() {
            guardianSection.classList.toggle("hidden", !isChild.checked);
            newGuardianForm.classList.toggle("hidden", !newGuardianToggle.checked);
        }

        isChild.addEventListener("change", toggleGuardianVisibility);
        newGuardianToggle.addEventListener("change", toggleGuardianVisibility);

        // Initialize on load
        toggleGuardianVisibility();

        document.getElementById("lookup-btn").addEventListener("click", function() {
            lookupNationalId();
        });
        document.getElementById("lookup-national-id").addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                lookupNationalId();
            }
        });

        function lookupNationalId() {
            const input = document.getElementById("lookup-national-id");
            const result = document.getElementById("lookup-result");
            const nationalId = input.value.trim();

            if (!nationalId) {
                result.innerHTML = '<p class="text-sm text-red-600">Please enter a National ID.</p>';
                return;
            }

            result.innerHTML = '<p class="text-sm text-gray-600">Searching...</p>';

            fetch("{{ route('patients.search.national-id') }}?national_id=" + encodeURIComponent(nationalId), {
                headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.found) {
                    const p = data.patient;
                    result.innerHTML =
                        '<div class="p-3 bg-yellow-100 border border-yellow-400 rounded-lg">' +
                        '<p class="text-sm text-yellow-800"><strong>Record already exists:</strong> ' + p.full_name +
                        ' (DHP ID: ' + p.dhp_id + ', Age: ' + p.age + ')</p>' +
                        '<a href="/patients/' + p.id + '" class="text-sm text-blue-600 hover:underline">View existing record</a>' +
                        '</div>';
                } else {
                    result.innerHTML =
                        '<p class="text-sm text-green-700">No existing record found with this National ID. You may proceed to register.</p>';
                }
            })
            .catch(function() {
                result.innerHTML = '<p class="text-sm text-red-600">Lookup failed. You may still register manually.</p>';
            });
        }
    </script>
</x-app-layout>
