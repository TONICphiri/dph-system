<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Pharmacy · Dispensing</p>
            <h2 class="text-2xl font-extrabold leading-tight">{{ $patient->full_name }}</h2>
            <p class="dhp-mono !text-dhp-100">{{ $patient->dhp_id }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl">
        <div class="dhp-card dhp-card-pad mb-6">
            <h3 class="dhp-section-title">Pending prescriptions</h3>
            <p class="dhp-section-sub">Stock is checked per facility before dispensing. Expired or insufficient stock blocks dispensing with a clear reason.</p>

            @if($prescriptions->isEmpty())
                <div class="dhp-empty mt-4">
                    <p class="font-bold text-dhp-900">No pending prescriptions</p>
                    <p class="text-sm text-slate-500">New prescriptions from consultation will appear here.</p>
                </div>
            @else
                <div class="dhp-table-wrap mt-4">
                    <table class="dhp-table">
                        <thead><tr><th>Medication</th><th>Dose</th><th>Qty</th><th>Status</th><th><span class="sr-only">Action</span></th></tr></thead>
                        <tbody>
                            @foreach($prescriptions as $prescription)
                                <tr>
                                    <td class="font-semibold">{{ $prescription->medication_name }}</td>
                                    <td>{{ $prescription->dose }} · {{ $prescription->frequency }}</td>
                                    <td class="tabular-nums">{{ $prescription->quantity ?? '—' }}</td>
                                    <td>
                                        @if($prescription->status === 'pending')
                                            <span class="dhp-badge badge-pending">Pending</span>
                                        @else
                                            <span class="dhp-badge badge-dispensed">{{ ucfirst($prescription->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($prescription->status === 'pending')
                                            <form action="{{ route('pharmacy.dispense') }}" method="POST" class="flex items-center gap-2" onsubmit="return confirm('Dispense {{ addslashes($prescription->medication_name) }} for {{ addslashes($patient->full_name) }}?');">
                                                @csrf
                                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                <input type="hidden" name="prescription_id" value="{{ $prescription->id }}">
                                                <label class="sr-only" for="qty-{{ $prescription->id }}">Quantity</label>
                                                <input type="number" id="qty-{{ $prescription->id }}" name="quantity_dispensed" min="1" max="10000" placeholder="{{ $prescription->quantity ?? 'Qty' }}" class="dhp-input !w-24 !py-1.5" />
                                                <button type="submit" class="btn-primary !min-h-[40px] !px-3 !py-1.5 !text-xs">Dispense</button>
                                            </form>
                                        @else
                                            <span class="dhp-badge badge-neutral">{{ ucfirst($prescription->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="dhp-card dhp-card-pad">
            <h3 class="dhp-section-title">Facility stock levels</h3>
            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
                @forelse($inventory as $item)
                    <div class="rounded-2xl border p-4 {{ $item->status === 'out_of_stock' ? 'border-rose-200 bg-rose-50' : ($item->status === 'low_stock' ? 'border-amber-200 bg-amber-50' : 'border-[#DCE8E8] bg-white') }}">
                        <p class="text-sm font-bold text-dhp-900">{{ $item->medication_name }}</p>
                        <p class="mt-1 text-sm tabular-nums">Stock: <strong>{{ $item->current_stock }}</strong> {{ $item->unit_of_measurement }}</p>
                        @if($item->status === 'low_stock')
                            <p class="dhp-badge badge-pending mt-2">Low stock</p>
                        @elseif($item->status === 'out_of_stock')
                            <p class="dhp-badge badge-emergency mt-2">Out of stock</p>
                        @elseif($item->status === 'expired')
                            <p class="dhp-badge badge-emergency mt-2">Expired</p>
                        @endif
                    </div>
                @empty
                    <p class="col-span-full text-sm text-slate-500">No stock records for this facility yet.</p>
                @endforelse
            </div>
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary mt-5">Back to patient</a>
        </div>
    </div>
</x-app-layout>
