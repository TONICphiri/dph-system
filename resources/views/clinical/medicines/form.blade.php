@php $editing = $medicine->exists; @endphp
<x-layouts.app :title="$editing ? 'Update medicine' : 'Add medicine'">
    <x-page-header :title="$editing ? $medicine->displayName() : 'Add a medicine'" description="Record the quantity in the smallest unit dispensed, for example tablets or bottles.">
        <x-slot:breadcrumb><a href="{{ route('medicines.index') }}" class="hover:text-brand-700">Medicine stock</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Update' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('medicines.update', $medicine) : route('medicines.store') }}" class="panel max-w-2xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.input name="name" label="Medicine name" :value="$medicine->name" required placeholder="For example, Paracetamol" />
            <x-field.input name="strength" label="Strength" :value="$medicine->strength" placeholder="For example, 500 mg" />
            <x-field.select name="dosage_form" label="Form" :options="$dosageForms" :value="$medicine->dosage_form" required />
            <x-field.input name="expiry_date" label="Expiry date" type="date" :value="$medicine->expiry_date?->toDateString()" />
            <x-field.input name="stock_quantity" label="Quantity in stock" type="number" min="0" :value="$medicine->stock_quantity ?? 0" required />
            <x-field.input name="reorder_level" label="Reorder level" type="number" min="0" :value="$medicine->reorder_level ?? 50" help="The dashboard warns when stock falls to this level." required />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('medicines.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save medicine</button>
        </div>
    </form>
</x-layouts.app>
