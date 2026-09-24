<x-layouts.app title="Pharmacy">
    <x-page-header title="Pharmacy" description="Prescriptions sent from consultations and wards at this facility. Dispensing reduces stock automatically.">
        <x-slot:actions><a href="{{ route('medicines.index') }}" class="btn-secondary"><x-icon name="box" class="h-4 w-4" /> Medicine stock</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        <div class="flex border-b border-line">
            @foreach ($statuses as $value => $label)
                <a href="{{ route('pharmacy.index', ['status' => $value]) }}" class="-mb-px border-b-2 px-4 py-3 text-sm font-medium {{ $status === $value ? 'border-brand-700 text-brand-800' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </div>
        <x-search-bar placeholder="Patient name or passport number"><input type="hidden" name="status" value="{{ $status }}"></x-search-bar>

        @if ($prescriptions->isEmpty())
            <x-empty title="No prescriptions here" icon="pill">{{ $status === 'pending' ? 'New prescriptions appear here when a doctor completes a consultation.' : '' }}</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Patient</th><th>From</th><th>Medicines</th><th>Prescribed by</th><th>{{ $status === 'pending' ? 'Waiting since' : 'Processed' }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($prescriptions as $prescription)
                            <tr>
                                <td><x-patient-cell :patient="$prescription->patient" :link="false" /></td>
                                <td>{{ $prescription->admission ? $prescription->admission->ward?->name ?? 'Inpatient' : 'Outpatient' }}</td>
                                <td class="text-sm">{{ $prescription->items->pluck('medicine_name')->join(', ') }}</td>
                                <td>{{ $prescription->prescriber?->name }}</td>
                                <td class="whitespace-nowrap">{{ ($prescription->dispensed_at ?? $prescription->created_at)->format('j M H:i') }}</td>
                                <td class="text-right"><a href="{{ route('pharmacy.show', $prescription) }}" class="{{ $status === 'pending' ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ $status === 'pending' ? 'Dispense' : 'View' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $prescriptions->links() }}
        @endif
    </section>
</x-layouts.app>
