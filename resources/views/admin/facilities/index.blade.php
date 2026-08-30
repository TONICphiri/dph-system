<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Facility Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Facilities</h3>
                <a href="{{ route('facilities.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-sky-800 focus:bg-sky-800 active:bg-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Add Facility
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm text-left text-slate-700">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Name</th>
                                <th class="px-4 py-3 font-semibold">Code</th>
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 font-semibold">District</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($facilities as $facility)
                                <tr>
                                    <td class="px-4 py-3">{{ $facility->name }}</td>
                                    <td class="px-4 py-3">{{ $facility->facility_code }}</td>
                                    <td class="px-4 py-3">{{ $facility->facility_type }}</td>
                                    <td class="px-4 py-3">{{ $facility->district }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $facility->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $facility->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 flex gap-2">
                                        <a href="{{ route('facilities.edit', $facility) }}" class="inline-flex items-center px-3 py-1.5 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-amber-600">Edit</a>
                                        <form action="{{ route('facilities.destroy', $facility) }}" method="POST" onsubmit="return confirm('Delete this facility?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-rose-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-rose-700">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No facilities found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
