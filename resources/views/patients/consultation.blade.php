@extends('layouts.app')

@section('content')
<div class="container mx-4 py-8">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Consultation - {{ $patient->full_name }}</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Record consultation details</p>
                    
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
</div>
@endsection