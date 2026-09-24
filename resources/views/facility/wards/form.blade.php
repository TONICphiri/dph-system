@php $editing = $ward->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit ward' : 'Add ward'">
    <x-page-header :title="$editing ? 'Edit '.$ward->name : 'Add a ward'" description="Ward types are managed by the System Administrator in System settings.">
        <x-slot:breadcrumb><a href="{{ route('facility.wards.index') }}" class="hover:text-brand-700">Wards and beds</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('facility.wards.update', $ward) : route('facility.wards.store') }}" class="panel max-w-2xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.input name="name" label="Ward name" :value="$ward->name" class="sm:col-span-2" required />
            <x-field.select name="ward_type" label="Ward type" :options="$wardTypes" :value="$ward->ward_type" required />
            <x-field.select name="gender_restriction" label="Patients" :options="$genders" :value="$ward->gender_restriction" :placeholder="false" required />
            @if ($editing)
                <x-field.select name="status" label="Status" :options="$statuses" :value="$ward->status" :placeholder="false" required />
            @else
                <x-field.input name="bed_count" label="Number of beds" type="number" min="1" max="200" value="10" help="Beds are numbered automatically. More can be added later." required />
            @endif
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('facility.wards.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save ward</button>
        </div>
    </form>
</x-layouts.app>
