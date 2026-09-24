<x-layouts.app title="Bed board">
    <x-page-header title="Bed board" description="The current state of every bed at this facility.">
        <x-slot:actions>
            <span class="flex items-center gap-4 text-[13px] text-muted">
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 border border-brand-300 bg-brand-50"></span> Available</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 bg-brand-800"></span> Occupied</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 border border-gold-600/40 bg-gold-100"></span> Maintenance</span>
            </span>
        </x-slot:actions>
    </x-page-header>

    @forelse ($wards as $ward)
        <section class="panel mb-6">
            <div class="panel-header">
                <h2 class="panel-title">{{ $ward->name }} <span class="font-normal text-muted">{{ $ward->ward_type }}, {{ strtolower($ward->gender_restriction->label()) }}</span></h2>
                <span class="text-sm text-muted">{{ $ward->beds->where('status', \App\Enums\BedStatus::Available)->count() }} of {{ $ward->beds->count() }} available</span>
            </div>
            <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($ward->beds as $bed)
                    @php $admission = $bed->currentAdmission; @endphp
                    @if ($admission)
                        <a href="{{ route('admissions.show', $admission) }}" class="block min-h-[76px] bg-brand-800 p-3 text-white hover:bg-brand-900">
                            <p class="mono text-[12px] text-brand-200">{{ $bed->bed_number }}</p>
                            <p class="mt-1 truncate text-sm font-medium">{{ $admission->patient->full_name }}</p>
                            <p class="text-[12px] text-brand-200">Day {{ $admission->lengthOfStayInDays() }}</p>
                        </a>
                    @elseif ($bed->status === \App\Enums\BedStatus::Maintenance)
                        <div class="min-h-[76px] border border-gold-600/40 bg-gold-100 p-3">
                            <p class="mono text-[12px] text-gold-700">{{ $bed->bed_number }}</p>
                            <p class="mt-1 text-sm text-gold-700">Maintenance</p>
                        </div>
                    @else
                        <div class="min-h-[76px] border border-brand-200 bg-brand-50 p-3">
                            <p class="mono text-[12px] text-brand-700">{{ $bed->bed_number }}</p>
                            <p class="mt-1 text-sm text-brand-800">Available</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @empty
        <section class="panel"><x-empty title="No wards have been set up at this facility" icon="bed" /></section>
    @endforelse
</x-layouts.app>
