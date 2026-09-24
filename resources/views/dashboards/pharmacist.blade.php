<x-layouts.app title="Dashboard">
    <x-page-header title="Pharmacy" description="Prescriptions waiting to be dispensed and medicines that need to be reordered.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.index') }}" class="btn-primary"><x-icon name="pill" class="h-4 w-4" /> Prescription queue</a>
            <a href="{{ route('medicines.index') }}" class="btn-secondary"><x-icon name="box" class="h-4 w-4" /> Medicine stock</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Waiting to be dispensed" :value="$stats['pending']" icon="clock" :href="route('pharmacy.index')" :tone="$stats['pending'] > 0 ? 'warning' : 'default'" />
        <x-stat label="Dispensed today" :value="$stats['dispensedToday']" icon="check" />
        <x-stat label="Medicines running low" :value="$stats['lowStock']" icon="box" :href="route('medicines.index', ['filter' => 'low'])" :tone="$stats['lowStock'] > 0 ? 'danger' : 'default'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="panel xl:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Prescription queue</h2></div>
            @forelse ($queue as $prescription)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <div>
                        <p class="font-medium">{{ $prescription->patient->full_name }}</p>
                        <p class="text-[13px] text-muted">{{ $prescription->items->count() }} {{ Str::plural('medicine', $prescription->items->count()) }}, prescribed by {{ $prescription->prescriber?->name }} {{ $prescription->created_at->diffForHumans() }}</p>
                    </div>
                    <a href="{{ route('pharmacy.show', $prescription) }}" class="btn-primary btn-sm">Dispense</a>
                </div>
            @empty
                <x-empty title="No prescriptions are waiting" icon="pill" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Running low</h2></div>
            @forelse ($lowStock as $medicine)
                <div class="flex items-center justify-between border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <span class="font-medium">{{ $medicine->displayName() }}</span>
                    <span class="badge-danger tabular-nums">{{ $medicine->stock_quantity }} left</span>
                </div>
            @empty
                <x-empty title="Stock levels are healthy" icon="box" />
            @endforelse
        </section>
    </div>
</x-layouts.app>
