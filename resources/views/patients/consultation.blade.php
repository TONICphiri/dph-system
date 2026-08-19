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
</x-app-layout>