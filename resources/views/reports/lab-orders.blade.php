{{-- Lab Orders Report Listing --}}
@can('view_reports')
<div class="bg-sky-50 border border-sky-200 rounded-lg p-6 mb-6">
    <h2 class="text-xl font-bold text-sky-800 mb-4">Lab Orders</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-sky-700 mb-1">Filter by Status</label>
            <select id="statusFilter" class="mt-1 block rounded border border-sky-300 py-1 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300">
                <option value="">All Statuses</option>
                <option value="requested">Requested</option>
                <option value="pending">Pending</option>
                <option value="results">Results</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-sky-700 mb-1">Filter by Test Type</label>
            <select id="testTypeFilter" class="mt-1 block rounded border border-sky-300 py-1 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300">
                <option value="">All Test Types</option>
                <option value="Malaria RDT">Malaria RDT</option>
                <option value="Blood Glucose">Blood Glucose</option>
                <option value="CBC">CBC</option>
                <option value="Urinalysis">Urinalysis</option>
                <option value="Chest X-ray">Chest X-ray</option>
                <option value="All">All</option>
            </select>
        </div>
    </div>
    
    <table class="min-w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-sky-900 text-white">
            <tr>
                <th class="p-3 text-left">Patient</th>
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
                <td class="p-3 font-medium text-sky-800">{{ $labOrder->patient->full_name }}</td>
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
                    @endcan
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{-- End Lab Orders Report Listing --}}

<script>
document.getElementById('statusFilter').addEventListener('change', function() {
    // Filter logic would be handled by AJAX or page reload
    console.log('Status filtered:', this.value);
});

document.getElementById('testTypeFilter').addEventListener('change', function() {
    // Filter logic
    console.log('Test type filtered:', this.value);
});
</script>