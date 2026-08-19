<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dispensed Medications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('reports.partials.date-range-form', ['route' => route('reports.dispensed-meds')])

            <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Prescriptions Dispensed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totals['total'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Units Dispensed</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totals['totalQuantity'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">By Medication</h3>
                    @if ($byMedication->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Medication</th>
                                        <th class="px-4 py-2">Prescriptions</th>
                                        <th class="px-4 py-2">Total Quantity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($byMedication as $row)
                                        <tr>
                                            <td class="px-4 py-2 font-medium">{{ $row->medication_name }}</td>
                                            <td class="px-4 py-2">{{ $row->prescriptions }}</td>
                                            <td class="px-4 py-2 font-bold">{{ $row->total_quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No medications dispensed in this period.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Recent Dispensing</h3>
                    @if ($recentDispensed->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Patient</th>
                                        <th class="px-4 py-2">Medication</th>
                                        <th class="px-4 py-2">Dose</th>
                                        <th class="px-4 py-2">Quantity</th>
                                        <th class="px-4 py-2">Dispensed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($recentDispensed as $prescription)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('patients.show', $prescription->patient) }}" class="text-blue-600 hover:underline">
                                                    {{ $prescription->patient->full_name }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2">{{ $prescription->medication_name }}</td>
                                            <td class="px-4 py-2">{{ $prescription->dose }}</td>
                                            <td class="px-4 py-2">{{ $prescription->quantity }}</td>
                                            <td class="px-4 py-2 text-xs">{{ $prescription->dispensed_at?->format('M d, Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No dispensing activity in this period.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>