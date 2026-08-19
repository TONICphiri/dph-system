<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Inventory Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Available</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['available'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Low Stock</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['lowStock'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Out of Stock</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['outOfStock'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Expired</p>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $stats['expired'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($items->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Medication</th>
                                        <th class="px-4 py-2">Strength</th>
                                        <th class="px-4 py-2">In Stock</th>
                                        <th class="px-4 py-2">Min / Max</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Expiry</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($items as $item)
                                        @php
                                            $statusClasses = match ($item->status) {
                                                'available' => 'bg-green-100 text-green-800',
                                                'low_stock' => 'bg-yellow-100 text-yellow-800',
                                                'out_of_stock' => 'bg-red-100 text-red-800',
                                                'expired' => 'bg-gray-200 text-gray-700',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2 font-medium">{{ $item->medication_name }}</td>
                                            <td class="px-4 py-2">{{ $item->strength ?? '—' }}</td>
                                            <td class="px-4 py-2 font-bold">{{ $item->current_stock }} {{ $item->unit_of_measurement }}</td>
                                            <td class="px-4 py-2 text-xs">{{ $item->minimum_stock }} / {{ $item->maximum_stock }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded {{ $statusClasses }}">
                                                    {{ str_replace('_', ' ', ucfirst($item->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No inventory items.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>