<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Patient Registry") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">Patient Search & Registry</h3>
                        @can("create_patient")
                            <a href="{{ route("patients.create") }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                + Register New Patient
                            </a>
                        @endcan
                    </div>

<form method="GET" action="{{ route("patients.index") }}" class="mb-6">
                        <div class="flex gap-4">
                            <input type="text" name="search" placeholder="Search by name, National ID, or DHP ID" 
                                   value="{{ request("search") }}" class="flex-1 px-4 py-2 border rounded" />
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                Search
                            </button>
                        </div>
                    </form>

                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                        <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2">QR / DHP ID Lookup</h3>
                        <p class="text-sm text-green-700 dark:text-green-300 mb-3">Scan or paste a Digital Health Passport ID to open the patient record instantly.</p>
                        <div class="flex gap-2">
                            <input type="text" id="lookup-dhp-id" placeholder="e.g. DHP-2026-00000001"
                                   class="flex-1 px-4 py-2 border rounded-md shadow-sm" />
                            <button type="button" id="lookup-dhp-btn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Open Record
                            </button>
                        </div>
                        <div id="lookup-dhp-result" class="mt-3"></div>
                    </div>

                    @if ($patients->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">DHP ID</th>
                                        <th class="px-4 py-2">Name</th>
                                        <th class="px-4 py-2">National ID</th>
                                        <th class="px-4 py-2">Age</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Registered</th>
                                        <th class="px-4 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($patients as $patient)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 font-mono text-blue-600">{{ $patient->dhp_id }}</td>
                                            <td class="px-4 py-2">{{ $patient->full_name }}</td>
                                            <td class="px-4 py-2">{{ $patient->national_id }}</td>
                                            <td class="px-4 py-2">{{ $patient->age ?? "N/A" }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded" 
                                                      :class="''{{ $patient->status === "active" ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800" }}''">
                                                    {{ ucfirst($patient->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $patient->registered_at->format("M d, Y") }}</td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route("patients.show", $patient) }}" class="text-blue-600 hover:underline">View</a>
                                                @can("edit_patient")
                                                    | <a href="{{ route("patients.edit", $patient) }}" class="text-blue-600 hover:underline">Edit</a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $patients->links() }}
                    @else
                        <p class="text-gray-500 text-center py-8">No patients found</p>
                    @endif
                </div>
            </div>
</div>
    </div>

    <script>
        const dhpInput = document.getElementById("lookup-dhp-id");
        const dhpBtn = document.getElementById("lookup-dhp-btn");
        const dhpResult = document.getElementById("lookup-dhp-result");

        function lookupDhpId() {
            const value = dhpInput.value.trim();
            if (!value) {
                dhpResult.innerHTML = '<p class="text-sm text-red-600">Please enter or scan a DHP ID.</p>';
                return;
            }

            dhpResult.innerHTML = '<p class="text-sm text-gray-600">Searching...</p>';

            fetch("{{ route("patients.search.dhp-id") }}?dhp_id=" + encodeURIComponent(value), {
                headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
            })
            .then(function(response) {
                if (response.status === 404) {
                    return response.json().then(function(data) {
                        throw new Error(data.message || "Patient not found");
                    });
                }
                return response.json();
            })
            .then(function(data) {
                if (data.found) {
                    const p = data.patient;
                    dhpResult.innerHTML =
                        '<div class="p-3 bg-green-100 border border-green-400 rounded-lg">' +
                        '<p class="text-sm text-green-800"><strong>Record found:</strong> ' + p.full_name +
                        ' (Age: ' + p.age + ')</p>' +
                        '<a href="/patients/' + p.id + '" class="text-sm text-green-700 underline font-semibold">Open patient record →</a>' +
                        '</div>';
                } else {
                    dhpResult.innerHTML =
                        '<p class="text-sm text-red-600">No patient found with this DHP ID.</p>';
                }
            })
            .catch(function(err) {
                dhpResult.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
            });
        }

        dhpBtn.addEventListener("click", lookupDhpId);
        dhpInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                lookupDhpId();
            }
        });
    </script>
</x-app-layout>
