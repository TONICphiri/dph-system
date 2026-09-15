<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Hospitals</p>
            <h2 class="text-2xl font-extrabold leading-tight">Facility management</h2>
            <p class="text-sm text-dhp-100">{{ $facilities->total() }} registered facilities nationwide.</p>
        </div>
    </x-slot>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="dhp-stat">
            <p class="dhp-stat-label">Facility Summary</p>
            <p class="dhp-stat-value">{{ $totalFacilityStaff ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $totalFacilityStaff ?? 0 }} Total Staff</p>
        </div>
        <div class="dhp-stat">
            <p class="dhp-stat-label">Active Staff</p>
            <p class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-700">{{ $activeFacilityStaff ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $activeFacilityStaff ?? 0 }} Active Staff</p>
        </div>
        <x-dhp-stat label="Facilities" :value="$facilities->total()" hint="Registered" />
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('facilities.create') }}" class="btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add facility
        </a>
    </div>

    @if($facilities->count())
        <div class="dhp-table-wrap">
            <table class="dhp-table">
                <thead><tr><th>Name</th><th>Code</th><th>Type</th><th>District</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach($facilities as $facility)
                        <tr>
                            <td class="font-semibold">{{ $facility->name }}</td>
                            <td class="dhp-mono">{{ $facility->facility_code }}</td>
                            <td>{{ $facility->facility_type }}</td>
                            <td>{{ $facility->district }}</td>
                            <td><span class="dhp-badge {{ $facility->status === 'active' ? 'badge-active' : 'badge-neutral' }}">{{ $facility->status }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('facilities.edit', $facility) }}" class="btn-warning !min-h-[40px] !px-3 !py-1.5 !text-xs">Edit</a>
                                <form action="{{ route('facilities.destroy', $facility) }}" method="POST" class="inline" onsubmit="return confirm('Delete facility {{ addslashes($facility->name) }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger !min-h-[40px] !px-3 !py-1.5 !text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $facilities->links() }}</div>
    @else
        <div class="dhp-empty">
            <p class="font-bold text-dhp-900">No facilities found</p>
            <a href="{{ route('facilities.create') }}" class="btn-primary mt-3">Register the first facility</a>
        </div>
    @endif
</x-app-layout>
