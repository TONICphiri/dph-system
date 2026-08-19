<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Consultation - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-muted mb-4">Record consultation details</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('consultation.save') }}" method="POST" class="space-y-4">
                        @method('POST')
                        @csrf
                        
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="hidden" name="encounter_type" value="consultation">
                        
                        <div class="form-group">
                            <label class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" class="form-control" rows="3"
                                placeholder="e.g., Cough, fever, abdominal pain">{{ old('chief_complaint') }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Examination Findings</label>
                            <textarea name="examination_findings" class="form-control" rows="3"
                                placeholder="e.g., Lung sounds clear, abdomen soft and non-tender">{{ old('examination_findings') }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="3"
                                placeholder="e.g., Upper respiratory infection">{{ old('diagnosis') }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Treatment Plan</label>
                            <textarea name="treatment_plan" class="form-control" rows="3"
                                placeholder="e.g., Paracetamol 500mg PO TID, rest and fluids">{{ old('treatment_plan') }}</textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_admission" id="requires_admission">
                            <label class="form-check-label" for="requires_admission">
                                Requires Admission
                            </label>
                        </div>

                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold">Prescriptions</h4>
                                <button type="button" id="add-prescription" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                    + Add Medication
                                </button>
                            </div>
                            <div id="prescriptions-container">
                                <div class="prescription-row grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 p-3 border border-gray-200 dark:border-gray-600 rounded">
                                    <div>
                                        <label class="form-label">Medication</label>
                                        <input type="text" name="prescriptions[0][medication_name]" list="inventory-medications"
                                               class="form-control" placeholder="e.g., Paracetamol" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Dose</label>
                                        <input type="text" name="prescriptions[0][dose]" class="form-control" placeholder="e.g., 500mg" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Frequency</label>
                                        <input type="text" name="prescriptions[0][frequency]" class="form-control" placeholder="e.g., 3x daily" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="prescriptions[0][quantity]" min="1" class="form-control" placeholder="e.g., 30">
                                    </div>
                                    <div>
                                        <label class="form-label">Duration</label>
                                        <input type="text" name="prescriptions[0][duration]" class="form-control" placeholder="e.g., 7 days">
                                    </div>
                                    <div>
                                        <label class="form-label">Instructions</label>
                                        <input type="text" name="prescriptions[0][instructions]" class="form-control" placeholder="e.g., After meals">
                                    </div>
                                </div>
                            </div>
                            <datalist id="inventory-medications">
                                @foreach ($inventory as $item)
                                    <option value="{{ $item->medication_name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-2"></i> Save Consultation
                            </button>
                            <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Patient
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const addBtn = document.getElementById("add-prescription");
        const container = document.getElementById("prescriptions-container");
        const inventoryList = document.getElementById("inventory-medications");

        function rowHtml(index) {
            return '<div class="prescription-row grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 p-3 border border-gray-200 dark:border-gray-600 rounded">' +
                '<div>' +
                '<label class="form-label">Medication</label>' +
                '<input type="text" name="prescriptions[' + index + '][medication_name]" list="inventory-medications" class="form-control" placeholder="e.g., Paracetamol" required>' +
                '</div>' +
                '<div>' +
                '<label class="form-label">Dose</label>' +
                '<input type="text" name="prescriptions[' + index + '][dose]" class="form-control" placeholder="e.g., 500mg" required>' +
                '</div>' +
                '<div>' +
                '<label class="form-label">Frequency</label>' +
                '<input type="text" name="prescriptions[' + index + '][frequency]" class="form-control" placeholder="e.g., 3x daily" required>' +
                '</div>' +
                '<div>' +
                '<label class="form-label">Quantity</label>' +
                '<input type="number" name="prescriptions[' + index + '][quantity]" min="1" class="form-control" placeholder="e.g., 30">' +
                '</div>' +
                '<div>' +
                '<label class="form-label">Duration</label>' +
                '<input type="text" name="prescriptions[' + index + '][duration]" class="form-control" placeholder="e.g., 7 days">' +
                '</div>' +
                '<div class="flex items-end">' +
                '<div class="flex-1">' +
                '<label class="form-label">Instructions</label>' +
                '<input type="text" name="prescriptions[' + index + '][instructions]" class="form-control" placeholder="e.g., After meals">' +
                '</div>' +
                '<button type="button" class="remove-prescription ml-2 px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">Remove</button>' +
                '</div>' +
                '</div>';
        }

        addBtn.addEventListener("click", function() {
            const index = container.querySelectorAll(".prescription-row").length;
            container.insertAdjacentHTML("beforeend", rowHtml(index));
        });

        container.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-prescription")) {
                e.target.closest(".prescription-row").remove();
                container.querySelectorAll(".prescription-row").forEach(function(row, i) {
                    row.querySelectorAll("[name]").forEach(function(input) {
                        const name = input.getAttribute("name").replace(/\[\d+\]/, "[" + i + "]");
                        input.setAttribute("name", name);
                    });
                });
            }
        });
    </script>
</x-app-layout>