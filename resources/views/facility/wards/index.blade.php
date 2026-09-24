<x-layouts.app title="Wards and beds">
    <x-page-header title="Wards and beds" description="Beds are released automatically when a patient is discharged.">
        <x-slot:actions>
            <a href="{{ route('facility.bed-board') }}" class="btn-secondary"><x-icon name="dashboard" class="h-4 w-4" /> Bed board</a>
            <a href="{{ route('facility.wards.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add ward</a>
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        @if ($wards->isEmpty())
            <x-empty title="No wards yet" icon="door">Add a ward and its beds so that admitted patients can be allocated a bed.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Ward</th><th>Type</th><th>Patients</th><th class="text-right">Beds</th><th class="text-right">Available</th><th class="text-right">Occupied</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($wards as $ward)
                            <tr>
                                <td><a href="{{ route('facility.wards.show', $ward) }}" class="font-medium hover:text-brand-700 hover:underline">{{ $ward->name }}</a></td>
                                <td>{{ $ward->ward_type }}</td>
                                <td>{{ $ward->gender_restriction->label() }}</td>
                                <td class="text-right tabular-nums">{{ $ward->beds_count }}</td>
                                <td class="text-right tabular-nums text-brand-700">{{ $ward->available_count }}</td>
                                <td class="text-right tabular-nums">{{ $ward->occupied_count }}</td>
                                <td><x-status :value="$ward->status" /></td>
                                <td class="text-right"><a href="{{ route('facility.wards.show', $ward) }}" class="link text-sm">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
