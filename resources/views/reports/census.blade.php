<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Patient Census') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('reports.partials.date-range-form', ['route' => route('reports.census')])

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Registered</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $census['total'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Registered In Period</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $census['registeredInPeriod'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $census['active'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Children / Adults</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $census['children'] }} / {{ $census['adults'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">By Gender</h3>
                        @if ($byGender->count())
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Gender</th>
                                        <th class="px-4 py-2">Patients</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($byGender as $row)
                                        <tr>
                                            <td class="px-4 py-2">{{ $row->gender ?? 'Not specified' }}</td>
                                            <td class="px-4 py-2 font-bold">{{ $row->total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-sm">No data available.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Top Districts</h3>
                        @if ($byDistrict->count())
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">District</th>
                                        <th class="px-4 py-2">Patients</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($byDistrict as $row)
                                        <tr>
                                            <td class="px-4 py-2">{{ $row->district }}</td>
                                            <td class="px-4 py-2 font-bold">{{ $row->total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-sm">No data available.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Recently Registered In Period</h3>
                    @if ($registeredInPeriod->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">DHP ID</th>
                                        <th class="px-4 py-2">Name</th>
                                        <th class="px-4 py-2">Gender</th>
                                        <th class="px-4 py-2">District</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Registered</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($registeredInPeriod as $patient)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-blue-600">{{ $patient->dhp_id }}</td>
                                            <td class="px-4 py-2">{{ $patient->full_name }}</td>
                                            <td class="px-4 py-2">{{ $patient->gender }}</td>
                                            <td class="px-4 py-2">{{ $patient->district ?? '—' }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded {{ $patient->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($patient->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $patient->registered_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No patients registered in this period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>