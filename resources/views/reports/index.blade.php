<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="{{ route('reports.census') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Patient Census</h3>
                    <p class="text-sm text-gray-500 mt-1">Total registered patients, gender, district and age breakdown.</p>
                </a>

                <a href="{{ route('reports.opd-visits') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">OPD Visits</h3>
                    <p class="text-sm text-gray-500 mt-1">Encounter volume by type and day within a date range.</p>
                </a>

                <a href="{{ route('reports.admissions') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Admissions</h3>
                    <p class="text-sm text-gray-500 mt-1">Admissions by ward and type, with average length of stay.</p>
                </a>

                <a href="{{ route('reports.dispensed-meds') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dispensed Medications</h3>
                    <p class="text-sm text-gray-500 mt-1">Medications dispensed, quantities and prescribing patterns.</p>
                </a>

                <a href="{{ route('reports.inventory') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Inventory</h3>
                    <p class="text-sm text-gray-500 mt-1">Current stock levels, low stock and out of stock items.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>