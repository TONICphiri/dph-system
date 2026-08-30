{{-- Lab Order Create Form --}}
@can('create_patient')
{{-- Patient Selection is passed from the route --}}
{{-- Hidden patient ID --}}
<input type="hidden" id="patientId" value="{{ $patient->id }}">

<div class="max-w-2xl mx-auto">
    <div class="bg-sky-50 border border-sky-200 rounded-lg p-6 mb-4">
        <h2 class="text-xl font-bold text-sky-800 mb-4">Create Lab Order</h2>
        
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lab.orders.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label for="test_type" class="block text-sm font-medium text-sky-700 mb-1">Test Type</label>
                <select name="test_type" id="test_type" class="mt-1 block w-full rounded border border-sky-300 py-2 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300">
                    <option value="">Select test type</option>
                    <option value="Malaria RDT">Malaria RDT</option>
                    <option value="Blood Glucose">Blood Glucose</option>
                    <option value="Complete Blood Count (CBC)">CBC</option>
                    <option value="Urinalysis">Urinalysis</option>
                    <option value="Chest X-ray">Chest X-ray</option>
                    <option value="HIV Test">HIV Test</option>
                    <option value="Hepatitis B">Hepatitis B</option>
                    <option value="Stool Exam">Stool Exam</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div>
                <label for="test_name" class="block text-sm font-medium text-sky-700 mb-1">Test Name</label>
                <input type="text" name="test_name" id="test_name" placeholder="e.g., Malaria Rapid Diagnostic Test" class="mt-1 block w-full rounded border border-sky-300 py-2 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-sky-700 mb-1">Description (Optional)</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded border border-sky-300 py-2 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300"></textarea>
            </div>
            
            <div>
                <label for="encounter_id" class="block text-sm font-medium text-sky-700 mb-1">Associated Encounter</label>
                <select name="encounter_id" id="encounter_id" class="mt-1 block w-full rounded border border-sky-300 py-2 px-3 text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300">
                    <option value="">None (use latest encounter)</option>
                    @foreach ($patient->encounters as $encounter)
                        <option value="{{ $encounter->id }}" {{ $encounter->id == $latestEncounter->id ? 'selected' : '' }}>
                            {{ $encounter->encounter_type }} - {{ $encounter->encounter_date->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-sky-600 text-white font-medium py-2 px-4 rounded hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 transition">
                    Create Lab Order
                </button>
                <a href="{{ route('patients.show', $patient) }}" class="bg-gray-100 text-gray-800 font-medium py-2 px-4 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
{{-- End Lab Order Create Form --}}