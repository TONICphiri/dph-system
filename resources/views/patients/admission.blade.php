@extends('layouts.app')

@section('content')
<div class="container mx-4 py-8">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Admission - {{ $patient->full_name }}</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Admit patient to inpatient ward</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admission.create') }}" method="POST" class="space-y-4">
                        @method('POST')
                        @csrf
                        
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        
                        <div class="form-group">
                            <label class="form-label">Bed Number</label>
                            <input type="text" name="bed_number" class="form-control"
                                placeholder="e.g., WARD-A, Bed 105">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Ward</label>
                            <input type="text" name="ward" class="form-control"
                                placeholder="e.g., Pediatrics, General Ward">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Admission Type</label>
                            <select name="admission_type" class="form-control">
                                <option value="">-- Select Admission Type --</option>
                                <option value="emergency">Emergency</option>
                                <option value="elective">Elective</option>
                                <option value="urgent">Urgent</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-hospital-me me-2"></i> Admit Patient
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