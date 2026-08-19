<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\Guardian;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Display a listing of patients
     */
    public function index(Request $request)
    {
        $this->authorize('view_patients');
        
        $query = Patient::query();
        
        // Search by name or national ID
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('dhp_id', 'like', "%{$search}%");
        }

        // Filter by facility
        if ($request->filled('facility_id')) {
            $query->where('registered_by_facility_id', $request->input('facility_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginate results
        $patients = $query->orderByDesc('registered_at')->paginate(15);
        
        return view('patients.index', compact('patients'));
    }

    /**
     * Show patient registration form
     */
    public function create()
    {
        $this->authorize('create_patient');
        
        $guardians = Guardian::where('status', 'active')->get();
        return view('patients.create', compact('guardians'));
    }

    /**
     * Store a newly created patient
     */
    public function store(StorePatientRequest $request)
    {
        $this->authorize('create_patient');

        // Check if patient already exists by National ID
        if ($request->filled('national_id')) {
            $existingPatient = Patient::where('national_id', $request->national_id)->first();
            if ($existingPatient) {
                return redirect()->route('patients.show', $existingPatient)
                              ->with('info', 'Patient record already exists');
            }
        }

        try {
            DB::beginTransaction();

            // For children, allow inline guardian creation if no guardian selected
            $guardianId = $request->guardian_id;
            if ($request->boolean('is_child') && !$guardianId && $request->filled('guardian_first_name')) {
                $guardian = Guardian::create([
                    'first_name' => $request->guardian_first_name,
                    'last_name' => $request->guardian_last_name,
                    'national_id' => $request->guardian_national_id,
                    'phone_number' => $request->guardian_phone_number,
                    'relationship' => $request->guardian_relationship ?? 'Mother',
                    'status' => 'active',
                ]);
                $guardianId = $guardian->id;
            }

            // Generate DHP ID
            $dhpId = Patient::generateDhpId();

            // Create patient
            $patient = Patient::create([
                'national_id' => $request->national_id,
                'dhp_id' => $dhpId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'village' => $request->village,
                'district' => $request->district,
                'is_child' => $request->boolean('is_child', false),
                'guardian_id' => $guardianId,
                'registered_at' => now(),
                'registered_by_facility_id' => $this->user()->facility_id,
                'registered_by_user_id' => $this->user()->id,
                'status' => 'active',
            ]);

            DB::commit();

            return redirect()->route('patients.show', $patient)
                          ->with('success', "Patient {$patient->full_name} registered successfully. DHP ID: {$patient->dhp_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Patient registration failed', [
                'error' => $e->getMessage(),
                'national_id' => $request->national_id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to register patient. Please try again.');
        }
    }

    /**
     * Display specified patient
     */
    public function show(Patient $patient)
    {
        $this->authorize('view_patient');
        
        $encounters = $patient->encounters()->with('vitals', 'prescriptions')->get();
        $admissions = $patient->admissions()->get();
        $guardian = $patient->guardian;
        
        return view('patients.show', compact('patient', 'encounters', 'admissions', 'guardian'));
    }

    /**
     * Show edit form for patient
     */
    public function edit(Patient $patient)
    {
        $this->authorize('edit_patient');
        
        $guardians = Guardian::where('status', 'active')->get();
        return view('patients.edit', compact('patient', 'guardians'));
    }

    /**
     * Update patient data
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $this->authorize('edit_patient');

        try {
            $patient->update($request->validated());
            
            return redirect()->route('patients.show', $patient)
                          ->with('success', 'Patient information updated successfully');
        } catch (\Exception $e) {
            \Log::error('Patient update failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to update patient information');
        }
    }

    /**
     * Search patient by National ID
     */
    public function searchByNationalId(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string|max:20',
        ]);

        $patient = Patient::where('national_id', $request->national_id)->first();

        if (!$patient) {
            return response()->json([
                'found' => false,
                'message' => 'No patient found with this National ID',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'patient' => [
                'id' => $patient->id,
                'dhp_id' => $patient->dhp_id,
                'full_name' => $patient->full_name,
                'national_id' => $patient->national_id,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'age' => $patient->age,
                'gender' => $patient->gender,
                'phone_number' => $patient->phone_number,
                'status' => $patient->status,
                'is_child' => $patient->is_child,
                'guardian' => $patient->guardian ? [
                    'full_name' => $patient->guardian->full_name,
                    'relationship' => $patient->guardian->relationship,
                ] : null,
            ],
        ]);
    }

/**
     * Search patient by DHP ID
     */
    public function searchByDhpId(Request $request)
    {
        $request->validate([
            'dhp_id' => 'required|string',
        ]);

        $patient = Patient::where('dhp_id', $request->dhp_id)->first();

        if (!$patient) {
            return response()->json([
                'found' => false,
                'message' => 'No patient found with this DHP ID',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'patient' => [
                'id' => $patient->id,
                'dhp_id' => $patient->dhp_id,
                'full_name' => $patient->full_name,
                'national_id' => $patient->national_id,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'age' => $patient->age,
                'gender' => $patient->gender,
                'status' => $patient->status,
            ],
        ]);
    }

/**
     * Show triage form for patient
     */
    public function triage(Patient $patient)
    {
        $this->authorize('triage_patient');
        
        $latestEncounter = $patient->encounters()->latest()->first();
        $existingVitals = $latestEncounter ? $latestEncounter->vitals()->first() : null;
        
        return view('patients.triage', compact('patient', 'existingVitals'));
    }

    /**
     * Save triage/vitals data for patient
     */
    public function saveTriage(Request $request)
    {
        $this->authorize('triage_patient');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'weight' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'systolic_bp' => 'nullable|numeric|min:50|max:250',
            'diastolic_bp' => 'nullable|numeric|min:30|max:200',
            'heart_rate' => 'nullable|numeric|min:30|max:200',
            'respiratory_rate' => 'nullable|numeric|min:10|max:50',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'priority_level' => 'nullable|string|in:Emergency,High,Medium,Low',
            'notes' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Check if patient has an encounter, if not create one
            $encounter = $patient->encounters()->latest()->first();
            if (!$encounter) {
                $encounter = $patient->encounters()->create([
                    'encounter_type' => 'triage',
                    'facility_id' => $this->user()->facility_id,
                    'user_id' => $this->user()->id,
                    'encounter_date' => now(),
                    'status' => 'registered',
                ]);
            }

            // Create or update vitals
            $vitalsData = array_merge($validated, [
                'patient_id' => $patient->id,
                'recorded_by_user_id' => $this->user()->id,
                'recorded_at' => now(),
            ]);

            $existingVitals = $encounter->vitals()->latest()->first();
            if ($existingVitals) {
                $existingVitals->update($vitalsData);
            } else {
                $existingVitals = $encounter->vitals()->create($vitalsData);
            }

            // Auto-prioritize based on abnormal vitals if no manual priority given
            if (empty($validated['priority_level'])) {
                $existingVitals->update(['priority_level' => $existingVitals->autoPriorityLevel()]);
            }

            // Mark encounter as triaged and move to the consultation queue
            $encounter->update(['status' => 'triaged']);
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', 'Triage vitals recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Triage failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to record triage data. Please try again.');
        }
    }

    /**
     * Show consultation form for patient
     */
    public function consultation(Patient $patient)
    {
        $this->authorize('consult_patient');
        
        $latestEncounter = $patient->encounters()->latest()->first();
        
        // Mark the encounter as in consultation so it leaves the waiting queue
        if ($latestEncounter && $latestEncounter->status === 'triaged') {
            $latestEncounter->update(['status' => 'consultation']);
        }
        
        return view('patients.consultation', compact('patient', 'latestEncounter'));
    }

    /**
     * Save consultation data for patient
     */
    public function saveConsultation(Request $request)
    {
        $this->authorize('consult_patient');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'chief_complaint' => 'required|string',
            'examination_findings' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'requires_admission' => 'nullable|boolean',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get or create latest encounter
            $encounter = $patient->encounters()->latest()->first();
            if (!$encounter) {
                $encounter = $patient->encounters()->create([
                    'encounter_type' => 'consultation',
                    'facility_id' => $this->user()->facility_id,
                    'user_id' => $this->user()->id,
                    'encounter_date' => now(),
                    'status' => 'active',
                ]);
            }
            
            // Update encounter with consultation data
            $encounter->update([
                'chief_complaint' => $validated['chief_complaint'],
                'examination_findings' => $validated['examination_findings'],
                'diagnosis' => $validated['diagnosis'],
                'treatment_plan' => $validated['treatment_plan'],
                'requires_admission' => $validated['requires_admission'] ?? false,
                'status' => 'completed',
            ]);
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', 'Consultation notes recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Consultation failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to record consultation data. Please try again.');
        }
    }

    /**
     * Show pharmacy form for patient
     */
    public function pharmacy(Patient $patient)
    {
        $this->authorize('dispense_medication');
        
        $latestEncounter = $patient->encounters()->latest()->first();
        $prescriptions = $latestEncounter ? $latestEncounter->prescriptions : collect();
        $inventory = \App\Models\Inventory::all();
        
        return view('patients.pharmacy', compact('patient', 'prescriptions', 'inventory'));
    }

    /**
     * Dispense medication and update inventory
     */
    public function dispenseMedication(Request $request)
    {
        $this->authorize('dispense_medication');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id',
            'quantity_dispensed' => 'nullable|integer|min:1',
        ]);
        
        try {
            DB::beginTransaction();
            
            $prescription = \App\Models\Prescription::find($validated['prescription_id']);
            
            $quantityDispensed = $validated['quantity_dispensed'] ?? $prescription->quantity;
            
            // Update prescription dispensed status
            $prescription->update([
                'status' => 'dispensed',
                'dispensed_at' => now(),
                'dispensed_by_user_id' => $this->user()->id,
                'quantity' => $quantityDispensed,
            ]);
            
            // Update inventory
            $inventoryItem = \App\Models\Inventory::where('medication_name', $prescription->medication_name)->first();
            if ($inventoryItem) {
                $inventoryItem->decrement('current_stock', $quantityDispensed);
                $inventoryItem->updateStatus();
            }
            
            // Create dispensing record or log
            \Log::info('Medication dispensed', [
                'prescription_id' => $prescription->id,
                'patient_id' => $patient->id,
                'quantity_dispensed' => $validated['quantity_dispensed'],
                'dispensed_by' => $this->user()->id,
            ]);
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', "Medication dispensed successfully. Stock updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Medication dispensing failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to dispense medication. Please try again.');
        }
    }

    /**
     * Show admission form for patient
     */
    public function admission(Patient $patient)
    {
        $this->authorize('admit_patient');
        
        $latestEncounter = $patient->encounters()->latest()->first();
        
        return view('patients.admission', compact('patient', 'latestEncounter'));
    }

    /**
     * Create admission record for patient
     */
    public function createAdmission(Request $request)
    {
        $this->authorize('admit_patient');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'bed_number' => 'required|string',
            'ward' => 'required|string',
            'admission_type' => 'required|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create encounter for the admission
            $encounter = $patient->encounters()->create([
                'encounter_type' => 'admission',
                'facility_id' => $this->user()->facility_id,
                'user_id' => $this->user()->id,
                'encounter_date' => now(),
                'status' => 'admitted',
            ]);
            
            // Create admission record
            $admission = $patient->admissions()->create([
                'encounter_id' => $encounter->id,
                'facility_id' => $this->user()->facility_id,
                'admitted_by_user_id' => $this->user()->id,
                'bed_number' => $validated['bed_number'],
                'ward_name' => $validated['ward'],
                'admission_type' => $validated['admission_type'],
                'admitted_at' => now(),
                'status' => 'active',
            ]);
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', "Patient admitted to ward {$validated['ward']}, Bed {$validated['bed_number']}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Admission failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to admit patient. Please try again.');
        }
    }

    /**
     * Record ward round observations
     */
    public function wardRound(Request $request)
    {
        $this->authorize('update_patient');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'temperature' => 'nullable|numeric|min:30|max:45',
            'heart_rate' => 'nullable|numeric|min:30|max:200',
            'respiratory_rate' => 'nullable|numeric|min:10|max:50',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'blood_pressure_systolic' => 'nullable|numeric|min:50|max:250',
            'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
            'weight' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get latest admission
            $latestAdmission = $patient->admissions()->latest()->first();
            
            // Get or create latest encounter
            $encounter = $patient->encounters()->latest()->first();
            if (!$encounter) {
                $encounter = $patient->encounters()->create([
                    'encounter_type' => 'ward_round',
                    'facility_id' => $this->user()->facility_id,
                    'user_id' => $this->user()->id,
                    'encounter_date' => now(),
                    'status' => 'active',
                ]);
            }
            
            // Update or create vital signs
            $vital = $encounter->vitals()->latest()->first();
            if (!$vital) {
                $vital = $encounter->vitals()->create([
                    'patient_id' => $patient->id,
                    'recorded_by_user_id' => $this->user()->id,
                    'recorded_at' => now(),
                ]);
            }
            
            // Update vital signs
            if (!empty($validated['temperature'])) $vital->temperature = $validated['temperature'];
            if (!empty($validated['heart_rate'])) $vital->heart_rate = $validated['heart_rate'];
            if (!empty($validated['respiratory_rate'])) $vital->respiratory_rate = $validated['respiratory_rate'];
            if (!empty($validated['oxygen_saturation'])) $vital->oxygen_saturation = $validated['oxygen_saturation'];
            if (!empty($validated['blood_pressure_systolic'])) $vital->systolic_bp = $validated['blood_pressure_systolic'];
            if (!empty($validated['blood_pressure_diastolic'])) $vital->diastolic_bp = $validated['blood_pressure_diastolic'];
            if (!empty($validated['weight'])) $vital->weight = $validated['weight'];
            if (!empty($validated['notes'])) $vital->notes = $validated['notes'];
            
            $vital->save();
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', 'Ward round observations recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ward round failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to record ward round data. Please try again.');
        }
    }

    /**
     * Discharge patient
     */
    public function dischargePatient(Request $request, Patient $patient)
    {
        $this->authorize('discharge_patient');
        
        $validated = $request->validate([
            'final_diagnosis' => 'required|string',
            'follow_up_instructions' => 'nullable|string',
            'discharge_date' => 'nullable|date',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get latest admission
            $latestAdmission = $patient->admissions()->latest()->first();
            
            if ($latestAdmission) {
                $latestAdmission->update([
                    'discharged_at' => $validated['discharge_date'] ?? now(),
                    'status' => 'discharged',
                    'discharge_summary' => $validated['final_diagnosis'],
                ]);
            }
            
            // Get latest encounter and mark as completed
            $latestEncounter = $patient->encounters()->latest()->first();
            if ($latestEncounter) {
                $latestEncounter->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('patients.show', $patient)
                ->with('success', "Patient discharged. Final diagnosis: {$validated['final_diagnosis']}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Discharge failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to discharge patient. Please try again.');
        }
    }

    /**
     * Show sync status
     */
    public function syncStatus()
    {
        $this->authorize('view_sync_queue');
        
        $syncQueue = \App\Models\SyncQueue::latest()->take(20)->get();
        $syncedCount = \App\Models\SyncQueue::where('status', 'synced')->count();
        $failedCount = \App\Models\SyncQueue::where('status', 'failed')->count();
        
        return view('patients.sync-status', compact('syncQueue', 'syncedCount', 'failedCount'));
    }

    /**
     * Upload sync data to national database
     */
    public function syncUpload(Request $request)
    {
        $this->authorize('upload_sync');
        
        $validated = $request->validate([
            'record_type' => 'required|string',
            'record_id' => 'required|integer',
            'data' => 'required|json',
        ]);
        
        try {
            $syncQueue = \App\Models\SyncQueue::create([
                'facility_id' => $this->user()->facility_id,
                'record_type' => $validated['record_type'],
                'record_id' => $validated['record_id'],
                'action' => 'create',
                'payload' => json_decode($validated['data'], true),
                'status' => 'pending',
            ]);
            
            // Attempt immediate sync (in production, this would be handled by background job)
            $synced = $this->attemptSync($syncQueue);
            
            if ($synced) {
                $syncQueue->update(['status' => 'synced']);
            }
            
            return response()->json([
                'success' => true,
                'sync_id' => $syncQueue->id,
                'status' => $syncQueue->status,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sync upload failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload sync data',
            ], 500);
        }
    }
    
    /**
     * Attempt to sync a sync queue record
     */
    protected function attemptSync(\App\Models\SyncQueue $syncQueue)
    {
        // In a full implementation, this would make API calls to the national database
        // For now, mark as synced after a short delay/simulation
        usleep(500000); // Simulate network delay
        
        // Simulate 95% success rate
        if (mt_rand(1, 100) <= 95) {
            return true;
        }
        
        return false;
    }
    
/**
     * Remove the specified patient from storage
     */
    public function destroy(Patient $patient)
    {
        $this->authorize('delete_patient');

        try {
            $patientName = $patient->full_name;
            $patient->delete();
            
            return redirect()->route('patients.index')
                          ->with('success', "Patient record for {$patientName} has been deleted");
        } catch (\Exception $e) {
            \Log::error('Patient deletion failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);
            
            return redirect()->back()->with('error', 'Failed to delete patient record');
        }
    }

    /**
     * Display QR code for patient
     */
    public function showQrCode(Patient $patient)
    {
        $this->authorize('view_patient');

        try {
            $qrCode = QrCodeService::generateQrCodeWithData(
                $patient->dhp_id,
                [
                    'patient_id' => $patient->id,
                    'full_name' => $patient->full_name,
                    'national_id' => $patient->national_id,
                ]
            );

            return view('patients.qr-code', compact('patient', 'qrCode'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate QR code');
        }
    }

    /**
     * Get QR code as JSON for AJAX
     */
    public function getQrCode(Patient $patient)
    {
        $this->authorize('view_patient');

        try {
            $qrCode = QrCodeService::generateQrCodeSvg($patient->dhp_id);

            return response()->json([
                'success' => true,
                'dhp_id' => $patient->dhp_id,
                'qr_code' => $qrCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code',
            ], 500);
        }
    }
}
