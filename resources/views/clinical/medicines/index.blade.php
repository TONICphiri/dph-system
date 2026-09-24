<x-layouts.app title="Medicine stock">
    <x-page-header title="Medicine stock" description="Medicines stocked at this facility. Doctors choose from this list when prescribing.">
        <x-slot:actions><a href="{{ route('medicines.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add medicine</a></x-slot:actions>
    </x-page-header>
    <section class="panel">
        <x-search-bar placeholder="Medicine name">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="h-4 w-4 border-line text-brand-700"> Low stock only</label>
        </x-search-bar>
        @if ($medicines->isEmpty())
            <x-empty title="No medicines found" icon="box" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Medicine</th><th>Form</th><th class="text-right">In stock</th><th class="text-right">Reorder level</th><th>Expiry date</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($medicines as $medicine)
                            @php $expired = $medicine->expiry_date && $medicine->expiry_date->isPast(); @endphp
                            <tr>
                                <td class="font-medium">{{ $medicine->displayName() }}</td>
                                <td>{{ $medicine->dosage_form }}</td>
                                <td class="text-right tabular-nums">{{ number_format($medicine->stock_quantity) }} @if ($medicine->isLowStock())<span class="badge-warning ml-1">Low</span>@endif</td>
                                <td class="text-right tabular-nums text-muted">{{ number_format($medicine->reorder_level) }}</td>
                                <td class="whitespace-nowrap {{ $expired ? 'font-medium text-red-700' : '' }}">{{ $medicine->expiry_date?->format('j M Y') ?? 'Not recorded' }}{{ $expired ? ', expired' : '' }}</td>
                                <td class="text-right"><a href="{{ route('medicines.edit', $medicine) }}" class="link text-sm">Update</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $medicines->links() }}
        @endif
    </section>
</x-layouts.app>
