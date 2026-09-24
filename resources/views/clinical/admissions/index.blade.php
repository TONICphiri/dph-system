<x-layouts.app title="Admissions">
    <x-page-header title="Admissions" description="Inpatients at this facility. Patients waiting for a bed are listed first.">
        <x-slot:actions>
            @can(\App\Enums\Permission::ViewWardStatus->value)
                <a href="{{ route('facility.bed-board') }}" class="btn-secondary"><x-icon name="bed" class="h-4 w-4" /> Bed board</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        <div class="flex overflow-x-auto border-b border-line">
            @foreach (['current' => 'Current inpatients', \App\Enums\AdmissionStatus::Discharged->value => 'Discharged'] as $value => $label)
                <a href="{{ route('admissions.index', ['status' => $value]) }}" class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium {{ $status === $value ? 'border-brand-700 text-brand-800' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-line px-5 py-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label for="ward" class="label">Ward</label>
                <select id="ward" name="ward" class="input w-56" onchange="this.form.submit()">
                    <option value="">All wards</option>
                    @foreach ($wards as $ward)<option value="{{ $ward->id }}" @selected(request('ward') == $ward->id)>{{ $ward->name }}</option>@endforeach
                </select>
            </div>
        </form>

        @if ($admissions->isEmpty())
            <x-empty title="No admissions to show" icon="bed" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Patient</th><th>Admitted</th><th>Reason</th><th>Ward and bed</th><th class="text-right">Days</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($admissions as $admission)
                            <tr>
                                <td><x-patient-cell :patient="$admission->patient" /></td>
                                <td class="whitespace-nowrap">{{ $admission->admitted_at->format('j M Y H:i') }}<p class="text-[12px] text-muted">{{ $admission->admittedBy?->name }}</p></td>
                                <td class="max-w-xs truncate text-sm" title="{{ $admission->admission_reason }}">{{ $admission->admission_reason }}</td>
                                <td>{{ $admission->ward ? $admission->ward->name.', bed '.$admission->bed?->bed_number : ($admission->preferred_ward_type ? 'Prefers '.$admission->preferred_ward_type : 'Not allocated') }}</td>
                                <td class="text-right tabular-nums">{{ $admission->lengthOfStayInDays() }}</td>
                                <td><x-status :value="$admission->status" /></td>
                                <td class="text-right"><a href="{{ route('admissions.show', $admission) }}" class="{{ $admission->status === \App\Enums\AdmissionStatus::AwaitingBed ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ $admission->status === \App\Enums\AdmissionStatus::AwaitingBed ? 'Allocate bed' : 'Open chart' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $admissions->links() }}
        @endif
    </section>
</x-layouts.app>
