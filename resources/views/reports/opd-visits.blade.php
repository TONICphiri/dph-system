<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('OPD Visits') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('reports.partials.date-range-form', ['route' => route('reports.opd-visits')])

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Visits</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totals['total'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $totals['completed'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">In Consultation</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totals['triaged'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Admitted</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $totals['admitted'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">By Visit Type</h3>
                        @if ($byType->count())
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Type</th>
                                        <th class="px-4 py-2">Visits</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($byType as $row)
                                        <tr>
                                            <td class="px-4 py-2">{{ $row->encounter_type }}</td>
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
                        <h3 class="text-lg font-semibold mb-4">Visits Per Day</h3>
                        @if ($daily->count())
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Date</th>
                                        <th class="px-4 py-2">Visits</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($daily as $row)
                                        <tr>
                                            <td class="px-4 py-2">{{ $row->day }}</td>
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
                    <h3 class="text-lg font-semibold mb-4">Recent Visits</h3>
                    @if ($recentVisits->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Patient</th>
                                        <th class="px-4 py-2">Type</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($recentVisits as $encounter)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('patients.show', $encounter->patient) }}" class="text-blue-600 hover:underline">
                                                    {{ $encounter->patient->full_name }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2">{{ $encounter->encounter_type }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">{{ ucfirst($encounter->status) }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $encounter->encounter_date->format('M d, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No visits in this period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>