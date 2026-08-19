<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ward Round - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-gray-500 mb-4">Record ward round observations</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('ward.round') }}" method="POST" class="space-y-4">
                        @method('POST')
                        @csrf
                        
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Temperature (°C)</label>
                                    <input type="number" step="0.1" name="temperature"
                                           class="form-control" placeholder="e.g., 36.8">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Heart Rate (bpm)</label>
                                    <input type="number" name="heart_rate"
                                           class="form-control" placeholder="e.g., 80">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Respiratory Rate</label>
                                    <input type="number" name="respiratory_rate"
                                           class="form-control" placeholder="e.g., 16">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Oxygen Saturation (%)</label>
                                    <input type="number" name="oxygen_saturation"
                                           class="form-control" placeholder="e.g., 98">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">BP Systolic</label>
                                    <input type="number" name="blood_pressure_systolic"
                                           class="form-control" placeholder="e.g., 120">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">BP Diastolic</label>
                                    <input type="number" name="blood_pressure_diastolic"
                                           class="form-control" placeholder="e.g., 80">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight"
                                           class="form-control" placeholder="e.g., 65.5">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"
                                        placeholder="Observations..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-stethoscope me-2"></i> Save Observations
                            </button>
                            <a href="{{ route('ward', $patient) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Ward
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>