<x-layouts.app :title="$ward->name">
    <x-page-header :title="$ward->name" :description="$ward->ward_type.' ward, '.strtolower($ward->gender_restriction->label()).' patients'">
        <x-slot:breadcrumb><a href="{{ route('facility.wards.index') }}" class="hover:text-brand-700">Wards and beds</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $ward->name }}</x-slot:breadcrumb>
        <x-slot:actions><a href="{{ route('facility.wards.edit', $ward) }}" class="btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Edit ward</a></x-slot:actions>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <section class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Beds</h2><x-status :value="$ward->status" /></div>
            @if ($ward->beds->isEmpty())
                <x-empty title="This ward has no beds" icon="bed" />
            @else
                <table class="table">
                    <thead><tr><th>Bed</th><th>Status</th><th>Patient</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($ward->beds as $bed)
                            <tr>
                                <td class="mono font-medium">{{ $bed->bed_number }}</td>
                                <td><x-status :value="$bed->status" /></td>
                                <td>{{ $bed->currentAdmission?->patient->full_name ?? '' }}</td>
                                <td class="text-right">
                                    @if ($bed->status !== \App\Enums\BedStatus::Occupied)
                                        @php $next = $bed->status === \App\Enums\BedStatus::Available ? \App\Enums\BedStatus::Maintenance : \App\Enums\BedStatus::Available; @endphp
                                        <form method="POST" action="{{ route('facility.beds.update', $bed) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $next->value }}">
                                            <button type="submit" class="link text-sm">{{ $next === \App\Enums\BedStatus::Maintenance ? 'Mark under maintenance' : 'Mark available' }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <form method="POST" action="{{ route('facility.wards.beds.store', $ward) }}" class="panel self-start">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Add beds</h2></div>
            <div class="panel-body"><x-field.input name="bed_count" label="Number of beds to add" type="number" min="1" max="100" value="1" required /></div>
            <div class="border-t border-line px-5 py-3"><button type="submit" class="btn-primary w-full">Add beds</button></div>
        </form>
    </div>
</x-layouts.app>
