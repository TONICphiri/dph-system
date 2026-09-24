<x-layouts.app title="Patients">
    <x-page-header title="Find a patient" description="Patient records are shared across every facility. Search by National ID, passport number, name or phone number.">
        <x-slot:actions>
            <a href="{{ route('patients.scan') }}" class="btn-secondary"><x-icon name="qr" class="h-4 w-4" /> Scan card</a>
            @can(\App\Enums\Permission::RegisterPatients->value)
                <a href="{{ route('patients.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register patient</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        <x-search-bar placeholder="National ID, passport number, name or phone number">
            <select name="sex" class="input md:w-40" aria-label="Sex">
                <option value="">Any sex</option>
                @foreach ($sexes as $value => $label)
                    <option value="{{ $value }}" @selected(request('sex') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-search-bar>

        @if ($patients->isEmpty())
            <x-empty title="{{ request('search') ? 'No patient matches your search' : 'No patients registered yet' }}" icon="users">
                @can(\App\Enums\Permission::RegisterPatients->value)
                    If the patient is new, <a href="{{ route('patients.create') }}" class="link">register the patient</a>.
                @endcan
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Patient</th><th>National ID</th><th>Sex</th><th>Date of birth</th><th>Phone</th><th>Registered at</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            <tr>
                                <td>
                                    <x-patient-cell :patient="$patient" />
                                    @if ($patient->mother)<p class="text-[12px] text-muted">Child of {{ $patient->mother->full_name }}</p>@endif
                                </td>
                                <td class="mono">{{ $patient->national_id ?? 'Not issued' }}</td>
                                <td>{{ $patient->sex->label() }}</td>
                                <td class="whitespace-nowrap">{{ $patient->date_of_birth->format('j M Y') }} <span class="text-muted">({{ $patient->age }})</span></td>
                                <td>{{ $patient->phone }}</td>
                                <td>{{ $patient->registeredFacility?->name }}</td>
                                <td><x-status :value="$patient->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $patients->links() }}
        @endif
    </section>
</x-layouts.app>
