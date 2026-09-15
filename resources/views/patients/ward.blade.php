<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ward - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            @if ($latestAdmission && $latestAdmission->status === 'active')
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Active admission: <strong>{{ $latestAdmission->ward_name ?? 'Unknown Ward' }}</strong>,
                        Bed {{ $latestAdmission->bed_number ?? 'N/A' }} —
                        admitted {{ $latestAdmission->admitted_at->diffForHumans() }}
                    </p>
                </div>
            @else
                <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">No active admission found for this patient.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Medication Administration -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Medication Administration Log</h3>

                        <form method="POST" action="{{ route("ward.medication-admin") }}" class="space-y-4 mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            @csrf
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                            <div>
                                <label class="form-label">Medication</label>
                                <select name="prescription_id" id="medication-select" class="form-control" onchange="fillMedicationDetails()">
                                    <option value="">-- Select from active prescriptions --</option>
                                    @foreach ($prescriptions as $prescription)
                                        <option value="{{ $prescription->id }}"
                                                data-name="{{ $prescription->medication_name }}"
                                                data-dose="{{ $prescription->dose }}"
                                                data-frequency="{{ $prescription->frequency }}">
                                            {{ $prescription->medication_name }} ({{ $prescription->dose }} - {{ $prescription->frequency }})
                                        </option>
                                    @endforeach
                                    <option value="manual">Other / manual entry</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="form-label">Medication Name</label>
                                    <input type="text" name="medication_name" id="medication-name" class="form-control" required
                                           placeholder="e.g., Paracetamol">
                                </div>
                                <div>
                                    <label class="form-label">Dose</label>
                                    <input type="text" name="dose" id="medication-dose" class="form-control" placeholder="e.g., 500mg">
                                </div>
                                <div>
                                    <label class="form-label">Route</label>
                                    <select name="route" class="form-control">
                                        <option value="PO">PO (oral)</option>
                                        <option value="IV">IV</option>
                                        <option value="IM">IM</option>
                                        <option value="SC">SC</option>
                                        <option value="Topical">Topical</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Any observations">
                            </div>

                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Record Administration
                            </button>
                        </form>

                        @if ($medicationAdministrations->count())
                            <ul class="divide-y">
                                @foreach ($medicationAdministrations as $admin)
                                    <li class="py-3">
                                        <div class="flex justify-between">
                                            <p class="font-semibold text-sm">{{ $admin->medication_name }}
                                                @if ($admin->dose)<span class="text-gray-500"> - {{ $admin->dose }}</span>@endif
                                            </p>
                                            <span class="text-xs text-gray-500">{{ $admin->administered_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            {{ $admin->route ?? 'PO' }} · by {{ $admin->administeredByUser?->name ?? 'Unknown' }}
                                            @if ($admin->notes)<span class="ml-2">· {{ $admin->notes }}</span>@endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 text-sm text-center py-6">No medications administered yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Progress Notes -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Daily Progress Notes</h3>

                        <form method="POST" action="{{ route("ward.progress-note") }}" class="space-y-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            @csrf
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <label class="form-label">New Progress Note</label>
                            <textarea name="note" rows="3" class="form-control" required
                                      placeholder="Patient's condition, response to treatment, plan for today..."></textarea>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Save Note
                            </button>
                        </form>

                        @if ($progressNotes->count())
                            <ul class="space-y-3">
                                @foreach ($progressNotes as $note)
                                    <li class="border-l-4 border-green-500 pl-4 py-2">
                                        <p class="text-sm">{{ $note->note }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $note->recorded_at->format("M d, Y H:i") }} · {{ $note->recordedByUser?->name ?? 'Unknown' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 text-sm text-center py-6">No progress notes yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('ward.round.form', $patient) }}" class="btn-primary flex-1">
                    Record ward round vitals
                </a>
                @can('discharge_patient')
                    <a href="{{ route('discharge', $patient) }}" class="btn-success flex-1">
                        Discharge patient
                    </a>
                @endcan
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary flex-1">
                    Back to patient
                </a>
            </div>
        </div>
    </div>

    <script>
        function fillMedicationDetails() {
            const select = document.getElementById("medication-select");
            const selected = select.options[select.selectedIndex];
            const nameInput = document.getElementById("medication-name");
            const doseInput = document.getElementById("medication-dose");

            if (selected.value && selected.value !== "manual" && selected.dataset.name) {
                nameInput.value = selected.dataset.name;
                doseInput.value = selected.dataset.dose || "";
            } else if (selected.value === "manual") {
                nameInput.value = "";
                doseInput.value = "";
            }
        }
    </script>
</x-app-layout>