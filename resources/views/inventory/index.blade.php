<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Inventory Management') }}
            </h2>
            <a href="{{ route("inventory.create") }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                + Add Medication
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

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
                    <form method="GET" action="{{ route("inventory.index") }}" class="mb-6">
                        <div class="flex gap-4">
                            <input type="text" name="search" placeholder="Search by medication name or code"
                                   value="{{ request("search") }}" class="flex-1 px-4 py-2 border rounded" />
                            <select name="status" class="px-4 py-2 border rounded">
                                <option value="">All Statuses</option>
                                <option value="available" {{ request("status") === "available" ? "selected" : "" }}>Available</option>
                                <option value="low_stock" {{ request("status") === "low_stock" ? "selected" : "" }}>Low Stock</option>
                                <option value="out_of_stock" {{ request("status") === "out_of_stock" ? "selected" : "" }}>Out of Stock</option>
                                <option value="expired" {{ request("status") === "expired" ? "selected" : "" }}>Expired</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                Filter
                            </button>
                        </div>
                    </form>

                    @if ($items->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Medication</th>
                                        <th class="px-4 py-2">Code</th>
                                        <th class="px-4 py-2">Strength</th>
                                        <th class="px-4 py-2">In Stock</th>
                                        <th class="px-4 py-2">Min / Max</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Expiry</th>
                                        <th class="px-4 py-2">Actions</th>
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
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 font-medium">{{ $item->medication_name }}</td>
                                            <td class="px-4 py-2 font-mono text-xs">{{ $item->medication_code ?? "—" }}</td>
                                            <td class="px-4 py-2">{{ $item->strength ?? "—" }}</td>
                                            <td class="px-4 py-2 font-bold {{ $item->current_stock <= $item->minimum_stock ? "text-red-600" : "" }}">
                                                {{ $item->current_stock }} {{ $item->unit_of_measurement }}
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $item->minimum_stock }} / {{ $item->maximum_stock }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded {{ $statusClasses }}">
                                                    {{ str_replace("_", " ", ucfirst($item->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-xs">{{ $item->expiry_date?->format("M d, Y") ?? "—" }}</td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route("inventory.edit", $item) }}" class="text-blue-600 hover:underline">Edit</a>
                                                <button type="button" class="ml-2 text-green-600 hover:underline"
                                                        onclick="document.getElementById('restock-{{ $item->id }}').classList.toggle('hidden')">
                                                    Restock
                                                </button>
                                                <form id="restock-{{ $item->id }}" method="POST" action="{{ route("inventory.restock", $item) }}" class="hidden mt-2">
                                                    @csrf
                                                    <div class="flex gap-2">
                                                        <input type="number" name="restock_quantity" min="1" required
                                                               placeholder="Qty" class="w-24 px-2 py-1 border rounded text-sm" />
                                                        <button type="submit" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                            Add Stock
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $items->links() }}
                    @else
                        <p class="text-gray-500 text-center py-8">No inventory items found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
