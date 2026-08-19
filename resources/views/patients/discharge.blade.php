<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Discharge - ') . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-gray-500 mb-4">Discharge patient from inpatient care</p>
                    
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