@php
    use App\Enums\Permission;
    $user = auth()->user();
    $columns = [
        ['title' => 'Waiting for vital signs', 'visits' => $waitingForVitals, 'icon' => 'activity', 'empty' => 'No patient is waiting for vital signs.'],
        ['title' => 'Waiting for the doctor', 'visits' => $waitingForDoctor, 'icon' => 'stethoscope', 'empty' => 'No patient is waiting for the doctor.'],
        ['title' => 'Waiting at the pharmacy', 'visits' => $awaitingPharmacy, 'icon' => 'pill', 'empty' => 'No patient is waiting at the pharmacy.'],
    ];
@endphp
<x-layouts.app title="Patient queue">
    <x-page-header title="Patient queue" description="Outpatients at this facility, in the order they arrived.">
        <x-slot:actions>
            @can(Permission::CheckInPatients->value)
                <a href="{{ route('patients.scan') }}" class="btn-secondary"><x-icon name="qr" class="h-4 w-4" /> Scan card</a>
                <a href="{{ route('patients.index') }}" class="btn-primary"><x-icon name="door" class="h-4 w-4" /> Check in a patient</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($columns as $column)
            <section class="panel self-start">
                <div class="panel-header">
                    <h2 class="panel-title flex items-center gap-2"><x-icon :name="$column['icon']" class="h-4 w-4 text-brand-700" /> {{ $column['title'] }}</h2>
                    <span class="badge-neutral tabular-nums">{{ $column['visits']->count() }}</span>
                </div>
                @forelse ($column['visits'] as $visit)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <div class="flex items-start justify-between gap-3">
                            <x-patient-cell :patient="$visit->patient" />
                            <span class="whitespace-nowrap text-[12px] text-muted" title="Checked in">{{ $visit->checked_in_at->format('H:i') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted">{{ $visit->reason_for_visit }}</p>
                        @if ($latest = $visit->vitals->last())
                            <p class="mt-1 text-[12px] text-muted">{{ $latest->temperature }} °C, {{ $latest->weight }} kg{{ $latest->bloodPressure() ? ', '.$latest->bloodPressure() : '' }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @if ($visit->status === \App\Enums\VisitStatus::WaitingForVitals && $user->can('recordVitals', $visit))
                                <a href="{{ route('vitals.create', $visit) }}" class="btn-primary btn-sm">Record vital signs</a>
                            @endif
                            @if ($visit->status !== \App\Enums\VisitStatus::AwaitingPharmacy && $user->can('consult', $visit))
                                <a href="{{ route('consultations.create', $visit) }}" class="{{ $visit->status === \App\Enums\VisitStatus::WaitingForDoctor ? 'btn-primary' : 'btn-secondary' }} btn-sm">Consult</a>
                            @endif
                            @if ($visit->status === \App\Enums\VisitStatus::AwaitingPharmacy && $user->can(Permission::DispenseMedication->value))
                                <a href="{{ route('pharmacy.index') }}" class="btn-primary btn-sm">Open pharmacy</a>
                            @endif
                            @can('cancel', $visit)
                                <form method="POST" action="{{ route('visits.cancel', $visit) }}" onsubmit="return confirm('Cancel this visit? Use this when the patient has left without being seen.')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="link text-sm text-muted">Cancel visit</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-center text-sm text-muted">{{ $column['empty'] }}</p>
                @endforelse
            </section>
        @endforeach
    </div>
</x-layouts.app>
