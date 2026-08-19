<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pharmacy - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-gray-500 mb-4">Dispense medication and update inventory</p>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($prescriptions->isEmpty())
                        <div class="px-4 py-3 rounded bg-blue-100 border border-blue-400 text-blue-700">
                            No prescriptions found for this patient.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Medication</th>
                                        <th class="px-4 py-2">Strength</th>
                                        <th class="px-4 py-2">Quantity</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($prescriptions as $prescription)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2">{{ $prescription->medication_name }}</td>
                                            <td class="px-4 py-2">{{ $prescription->dose }}</td>
                                            <td class="px-4 py-2">{{ $prescription->quantity }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded
                                                    {{ $prescription->status === 'dispensed' ? 'bg-yellow-100 text-yellow-800' : ($prescription->status === 'pending' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ ucfirst($prescription->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2">
                                                @if($prescription->status === 'pending')
                                                    <form action="{{ route('pharmacy.dispense') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                        <input type="hidden" name="prescription_id" value="{{ $prescription->id }}">
                                                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                                            Dispense
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm">
                                                        {{ ucfirst($prescription->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h5 class="font-semibold mt-8 mb-3">Inventory Stock Levels</h5>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($inventory as $item)
                                <div class="border border-gray-200 dark:border-gray-600 rounded p-4">
                                    <h5 class="font-semibold text-sm">{{ $item->medication_name }}</h5>
                                    <p class="text-sm mt-1">Stock: <strong>{{ $item->current_stock }}</strong> {{ $item->unit_of_measurement }}</p>
                                    @if($item->status === 'low_stock')
                                        <p class="text-xs text-yellow-600">Low stock</p>
                                    @elseif($item->status === 'out_of_stock')
                                        <p class="text-xs text-red-600">Out of stock</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6">
                        <a href="{{ route('patients.show', $patient) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            Back to Patient
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>