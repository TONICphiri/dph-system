@php $pending = $prescription->status === \App\Enums\PrescriptionStatus::Pending; $patient = $prescription->patient; @endphp
<x-layouts.app title="Prescription">
    <x-page-header :title="'Prescription for '.$patient->full_name" :description="'Prescribed by '.($prescription->prescriber?->name ?? 'unknown').' on '.$prescription->created_at->format('j F Y H:i').'.'">
        <x-slot:breadcrumb><a href="{{ route('pharmacy.index') }}" class="hover:text-brand-700">Pharmacy</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Prescription</x-slot:breadcrumb>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <section class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Medicines</h2><x-status :value="$prescription->status" /></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Medicine</th><th>Directions</th><th class="text-right">Quantity</th><th class="text-right">In stock</th></tr></thead>
                    <tbody>
                        @foreach ($prescription->items as $item)
                            @php $short = $pending && $item->medicine && $item->medicine->stock_quantity < $item->quantity; @endphp
                            <tr class="{{ $short ? 'bg-red-50' : '' }}">
                                <td class="font-medium">{{ $item->medicine_name }}</td>
                                <td>{{ $item->directions() }}@if ($item->instructions)<p class="text-[13px] text-muted">{{ $item->instructions }}</p>@endif</td>
                                <td class="text-right tabular-nums">{{ $item->quantity }}</td>
                                <td class="text-right tabular-nums {{ $short ? 'font-medium text-red-700' : '' }}">{{ $item->medicine ? $item->medicine->stock_quantity : 'Not stocked' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pending && $prescription->items->contains(fn ($item) => ! $item->medicine))
                <p class="border-t border-line bg-gold-100 px-5 py-2 text-[13px] text-gold-700">Medicines marked as not stocked are recorded as given but do not change the stock list. Advise the patient if they must buy them elsewhere.</p>
            @endif
            @if ($pending)
                <div class="flex flex-wrap justify-end gap-2 border-t border-line px-5 py-3">
                    <form method="POST" action="{{ route('pharmacy.cancel', $prescription) }}" onsubmit="return confirm('Cancel this prescription? The doctor will need to prescribe again.')">
                        @csrf<button type="submit" class="btn-secondary">Cancel prescription</button>
                    </form>
                    <form method="POST" action="{{ route('pharmacy.dispense', $prescription) }}">
                        @csrf<button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> Confirm dispensed</button>
                    </form>
                </div>
            @elseif ($prescription->dispenser)
                <p class="border-t border-line px-5 py-3 text-sm text-muted">Dispensed by {{ $prescription->dispenser->name }} on {{ $prescription->dispensed_at?->format('j F Y H:i') }}.</p>
            @endif
        </section>

        <aside class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">Patient</h2></div>
            <dl class="panel-body detail-list !grid-cols-1">
                <div><dt>Name</dt><dd>{{ $patient->full_name }}</dd></div>
                <div><dt>Passport number</dt><dd class="mono">{{ $patient->passport_number }}</dd></div>
                <div><dt>Age and sex</dt><dd>{{ $patient->age_label }}, {{ $patient->sex->label() }}</dd></div>
            </dl>
            <p class="border-t border-line px-5 py-3 text-[13px] text-muted">Pharmacy staff see prescriptions and dosage only. Other parts of the medical record are not shown.</p>
        </aside>
    </div>
</x-layouts.app>
