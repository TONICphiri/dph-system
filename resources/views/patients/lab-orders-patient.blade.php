{{-- Patient Lab Orders Listing --}}
<div class="bg-sky-50 border border-sky-200 rounded-lg p-6 mb-6">
    <h2 class="text-xl font-bold text-sky-800 mb-4">Lab Orders for {{ $patient->full_name }} (DHP ID: {{ $patient->dhp_id }})</h2>
    
    @if ($labOrders->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-sky-900 text-white">
                    <tr>
                        <th class="p-3 text-left">Test</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Requested</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($labOrders as $labOrder)
                    <tr class="border-b border-sky-100 hover:bg-sky-50">
                        <td class="p-3 font-medium text-sky-800">{{ $labOrder->test_name }}</td>
                        <td class="p-3 text-sky-600 text-sm">{{ ucfirst($labOrder->test_type) }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs @switch($labOrder->status)
                                @case('requested') bg-yellow-100 text-yellow-800
                                @case('pending') bg-orange-100 text-orange-800
                                @case('results') bg-green-100 text-green-800
                                @case('cancelled') bg-gray-100 text-gray-700
                            @default bg-gray-100 text-gray-700">
                                {{ ucfirst($labOrder->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-sky-600 text-sm">{{ $labOrder->requested_at->format('M d, Y') }}</td>
                        <td class="p-3 text-right">
                            @can('view_reports')
                            <a href="{{ route('lab.orders.show', $labOrder) }}" class="text-blue-600 text-sm hover:underline">View</a>
                            @endcan>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sky-600 text-sm">No lab orders found for this patient.</p>
    @endif
</div>