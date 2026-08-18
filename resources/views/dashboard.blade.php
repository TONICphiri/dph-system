<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Patients</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['totalPatients'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Registered Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['patientsToday'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Awaiting Triage</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['pendingTriage'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">In Consultation</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['awaitingConsultation'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Admissions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['activeAdmissions'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Admitted Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['admittedToday'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Low / Out of Stock</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['lowStock'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3">
                            @can('create_patient')
                                <a href="{{ route('patients.create') }}" class="px-4 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 text-center text-sm">Register Patient</a>
                            @endcan
                            @can('view_patients')
                                <a href="{{ route('patients.index') }}" class="px-4 py-3 bg-gray-600 text-white rounded hover:bg-gray-700 text-center text-sm">Patient Registry</a>
                            @endcan
                            @can('triage_patient')
                                <a href="{{ route('patients.index') }}" class="px-4 py-3 bg-cyan-600 text-white rounded hover:bg-cyan-700 text-center text-sm">Triage Patient</a>
                            @endcan
                            @can('consult_patient')
                                <a href="{{ route('patients.index') }}" class="px-4 py-3 bg-green-600 text-white rounded hover:bg-green-700 text-center text-sm">Consultation</a>
                            @endcan
                            @can('dispense_medication')
                                <a href="{{ route('patients.index') }}" class="px-4 py-3 bg-purple-600 text-white rounded hover:bg-purple-700 text-center text-sm">Pharmacy</a>
                            @endcan
                            @can('manage_inventory')
                                <a href="{{ route('patients.index') }}" class="px-4 py-3 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-center text-sm">Inventory</a>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Recent Encounters</h3>
                        @if ($recentEncounters->count())
                            <ul class="divide-y">
                                @foreach ($recentEncounters as $encounter)
                                    <li class="py-2 flex justify-between items-center">
                                        <a href="{{ route('patients.show', $encounter->patient) }}" class="text-blue-600 hover:underline">
                                            {{ $encounter->patient->full_name }}
                                        </a>
                                        <span class="text-sm text-gray-500">
                                            {{ ucfirst($encounter->encounter_type) }} · {{ $encounter->encounter_date->diffForHumans() }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 text-sm">No encounters recorded yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Recently Registered Patients</h3>
                    @if ($recentPatients->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">DHP ID</th>
                                        <th class="px-4 py-2">Name</th>
                                        <th class="px-4 py-2">National ID</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Registered</th>
                                        <th class="px-4 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($recentPatients as $patient)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 font-mono text-blue-600">{{ $patient->dhp_id }}</td>
                                            <td class="px-4 py-2">{{ $patient->full_name }}</td>
                                            <td class="px-4 py-2">{{ $patient->national_id }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded {{ $patient->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($patient->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $patient->registered_at->format('M d, Y') }}</td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('patients.show', $patient) }}" class="text-blue-600 hover:underline">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No patients registered yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>