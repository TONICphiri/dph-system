{{-- Individual Lab Order Details --}}
@can('view_reports')
<div class="bg-sky-50 border border-sky-200 rounded-lg p-6 max-w-2xl mx-auto">
    <h2 class="text-xl font-bold text-sky-800 mb-4">Lab Order Details</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <p class="text-sm text-sky-500 mb-2">Patient</p>
            <p class="font-medium text-sky-800">{{ $labOrder->patient->full_name }}</p>
            <p class="text-xs text-sky-500">{{ $labOrder->patient->dhp_id }}</p>
        </div>
        <div>
            <p class="text-sm text-sky-500 mb-2">DHP ID</p>
            <p class="font-medium text-sky-800">{{ $labOrder->patient->dhp_id }}</p>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-sm text-sky-500 mb-2">Test Name</p>
            <p class="font-medium text-sky-800 text-2xl">{{ $labOrder->test_name }}</p>
        </div>
        <div>
            <p class="text-sm text-sky-500 mb-2">Test Type</p>
            <p class="font-medium text-sky-800">{{ ucfirst($labOrder->test_type) }}</p>
        </div>
    </div>
    
    <div>
        <p class="text-sm text-sky-500 mb-2">Status</p>
        <span class="px-3 py-1 rounded text-lg @switch($labOrder->status)
            @case('requested') bg-yellow-100 text-yellow-800
            @case('pending') bg-orange-100 text-orange-800
            @case('results') bg-green-100 text-green-800
            @case('cancelled') bg-gray-100 text-gray-700
        @default bg-gray-100 text-gray-700">
            {{ ucfirst($labOrder->status) }}
        </span>
    </div>
    
    @if ($labOrder->status === 'results')
    <div>
        <p class="text-sm text-sky-500 mb-2">Result Value</p>
        <p class="font-medium text-green-700 text-2xl">{{ $labOrder->result_value }}</p>
        @if ($labOrder->result_units)
        <p class="text-xs text-sky-500">{{ $labOrder->result_units }}</p>
        @endif
    </div>
    @endif
    
    @if ($labOrder->result_description)
    <div>
        <p class="text-sm text-sky-500 mb-2">Result Description</p>
        <p class="text-sky-800">{{ $labOrder->result_description }}</p>
    </div>
    @endif
    
    <div class="mt-6 pt-4 border-t border-sky-200">
        <p class="text-sm text-sky-500 mb-2">Requested By</p>
        <p class="font-medium text-sky-800">{{ $labOrder->requestedBy ? $labOrder->requestedBy->full_name : 'Unknown' }}</p>
        <p class="text-xs text-sky-500">{{ $labOrder->requested_at->format('M d, Y H:i') }}</p>
    </div>
    
    @if ($labOrder->completed_at)
    <div>
        <p class="text-sm text-sky-500 mb-2">Completed At</p>
        <p class="font-medium text-sky-800">{{ $labOrder->completed_at->format('M d, Y H:i') }}</p>
    </div>
    @endif
    
    <div class="mt-6 pt-4 border-t border-sky-200">
        <p class="text-sm text-sky-500 mb-2">Description</p>
        @if ($labOrder->description)
        <p class="text-sky-700">{{ $labOrder->description }}</p>
        @else
        <p class="text-sky-500 italic">No description provided</p>
        @endif
    </div>
    
    @endif
</div>
{{-- End Individual Lab Order Details --}}