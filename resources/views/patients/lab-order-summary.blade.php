{{-- Patient Show Page: Lab Order Link --}}
@can('create_patient')
<a href="{{ route('lab.orders.create', $patient) }}" class="text-blue-600 hover:underline text-sm mb-3 block">
    <i class="fas fa-tubes me-1"></i> Create Lab Order
</a>
@endcan

<h2 class="text-xl font-bold text-sky-900 mb-4">Lab Orders</h2>

@forelse ($patient->labOrders as $labOrder)
    <div class="p-3 bg-sky-50 border border-sky-200 rounded-md mb-3">
        <div class="flex justify-between items-start">
            <div>
                <p class="font-medium text-sky-800">{{ $labOrder->test_name }}</p>
                <p class="text-xs text-sky-600">
                    Type: {{ ucfirst($labOrder->test_type) }} &bull;
                    Requested: {{ $labOrder->requested_at?->format('M d, Y H:i') ?? '—' }} &bull;
                    @if ($labOrder->status === 'results')
                        Results: {{ $labOrder->result_value }}
                        @if ($labOrder->result_units)
                            {{ $labOrder->result_units }}
                        @endif
                    @else
                        Status: <span class="text-orange-600 font-medium">{{ ucfirst($labOrder->status) }}</span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                @can('view_reports')
                <a href="{{ route('lab.orders.show', $labOrder) }}" class="text-blue-600 text-sm hover:underline">View</a>
                @endcan
            </div>
        </div>
    </div>
@empty
    <p class="text-sky-600 text-sm mb-3">No lab orders found for this patient.</p>
@endforelse