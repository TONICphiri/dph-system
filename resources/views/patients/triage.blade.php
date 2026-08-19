<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Triage - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-muted mb-4">Record vital signs for priority classification</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('triage.save') }}" method="POST" class="space-y-4">
                        @method('POST')
                        @csrf
                        
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="hidden" name="encounter_type" value="triage">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" 
                                           class="form-control @error('weight') is-invalid @enderror"
                                           value="{{ old('weight', $existingVitals?->weight ?? '') }}" 
                                           placeholder="e.g., 55.5">
                                    @error('weight')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Temperature (°C)</label>
                                    <input type="number" step="0.1" name="temperature"
                                           class="form-control @error('temperature') is-invalid @enderror"
                                           value="{{ old('temperature', $existingVitals?->temperature ?? '') }}" 
                                           placeholder="e.g., 36.8">
                                    @error('temperature')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Systolic BP</label>
                                    <input type="number" name="systolic_bp"
                                           class="form-control @error('systolic_bp') is-invalid @enderror"
                                           value="{{ old('systolic_bp', $existingVitals?->systolic_bp ?? '') }}" 
                                           placeholder="e.g., 120">
                                    @error('systolic_bp')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Diastolic BP</label>
                                    <input type="number" name="diastolic_bp"
                                           class="form-control @error('diastolic_bp') is-invalid @enderror"
                                           value="{{ old('diastolic_bp', $existingVitals?->diastolic_bp ?? '') }}" 
                                           placeholder="e.g., 80">
                                    @error('diastolic_bp')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Heart Rate (bpm)</label>
                                    <input type="number" name="heart_rate"
                                           class="form-control @error('heart_rate') is-invalid @enderror"
                                           value="{{ old('heart_rate', $existingVitals?->heart_rate ?? '') }}" 
                                           placeholder="e.g., 76">
                                    @error('heart_rate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Respiratory Rate</label>
                                    <input type="number" name="respiratory_rate"
                                           class="form-control @error('respiratory_rate') is-invalid @enderror"
                                           value="{{ old('respiratory_rate', $existingVitals?->respiratory_rate ?? '') }}" 
                                           placeholder="e.g., 16">
                                    @error('respiratory_rate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Oxygen Saturation (%)</label>
                                    <input type="number" name="oxygen_saturation"
                                           class="form-control @error('oxygen_saturation') is-invalid @enderror"
                                           value="{{ old('oxygen_saturation', $existingVitals?->oxygen_saturation ?? '') }}" 
                                           placeholder="e.g., 98">
                                    @error('oxygen_saturation')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Priority Level</label>
                                    <select name="priority_level" class="form-control">
                                        <option value="">-- Auto (based on vitals) --</option>
                                        <option value="Emergency" {{ old('priority_level', $existingVitals?->priority_level ?? '') === 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                        <option value="High" {{ old('priority_level', $existingVitals?->priority_level ?? '') === 'High' ? 'selected' : '' }}>High</option>
                                        <option value="Medium" {{ old('priority_level', $existingVitals?->priority_level ?? '') === 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="Low" {{ old('priority_level', $existingVitals?->priority_level ?? '') === 'Low' ? 'selected' : '' }}>Low</option>
                                    </select>
                                    @error('priority_level')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                rows="3">{{ old('notes', $existingVitals?->notes ?? '') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i> Save Triage
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