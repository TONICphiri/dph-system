@extends('layouts.app')

@section('content')
<div class="container mx-4 py-8">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Discharge - {{ $patient->full_name }}</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Discharge patient from inpatient care</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('discharge', ['patient' => $patient->id]) }}" method="POST" class="space-y-4">
                        @method('POST')
                        @csrf
                        
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        
                        <div class="form-group">
                            <label class="form-label">Final Diagnosis</label>
                            <textarea name="final_diagnosis" class="form-control" rows="3"
                                placeholder="e.g., Pneumonia - resolved with antibiotics">{{ old('final_diagnosis') }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Follow-up Instructions</label>
                            <textarea name="follow_up_instructions" class="form-control" rows="3"
                                placeholder="e.g., Return in 2 weeks for check-up">{{ old('follow_up_instructions') }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Discharge Date</label>
                            <input type="date" name="discharge_date" class="form-control">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-door-open me-2"></i> Discharge Patient
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