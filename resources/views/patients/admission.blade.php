<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admission - ') . $patient->full_name }}
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
                    <p class="text-gray-500 mb-4">Admit patient to inpatient ward</p>
                    
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
</x-app-layout>