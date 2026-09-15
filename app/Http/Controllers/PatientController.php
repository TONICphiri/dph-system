<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Jobs\ProcessSyncQueue;
use App\Models\Patient;
use App\Models\Guardian;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Prescription;
use App\Models\Admission;
use App\Models\Inventory;
use App\Models\SyncQueue;
use App\Models\Vital;
use App\Services\QrCodeService;
use App\Services\SyncService;
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

        $request->validate([
            'search' => 'nullable|string|max:60',
            'facility_id' => 'nullable|integer|exists:facilities,id',
            'status' => 'nullable|in:active,inactive,deceased',
        ]);

        $query = Patient::query();

        // Search by name or national ID — grouped so ORs never leak outside filters.
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('dhp_id', 'like', "%{$search}%");
            });
        }

        // Filter by facility
        if ($request->filled('facility_id')) {
            $query->where('registered_by_facility_id', $request->input('facility_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginate results (preserve filters across pages)
        $patients = $query->orderByDesc('registered_at')->paginate(15)->withQueryString();
        
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

        $facilityId = $this->user()->facility_id;
        if (!$facilityId && !$this->user()->isNationalAdmin()) {
            return redirect()->back()->with('error', 'Your account is not linked to a facility. Contact an administrator.');
        }

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

            // Generate Health Passport ID: DISTRICT-FACILITY#-YEAR-SEQ
            $dhpId = Patient::generateDhpId($request->district, $facilityId);

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
                'traditional_authority' => $request->traditional_authority,
                'district' => $request->district,
                'is_child' => $request->boolean('is_child', false),
                'guardian_id' => $guardianId,
                'registered_at' => now(),
                'registered_by_facility_id' => $facilityId,
                'registered_by_user_id' => $this->user()->id,
                'status' => 'active',
            ]);

            DB::commit();

            AuditLog::create([
                'action' => 'create',
                'subject_type' => Patient::class,
                'subject_id' => $patient->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => 'Patient registered: ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') at ' . ($this->user()->facility->name ?? 'National Registry'),
            ]);

            SyncService::enqueue('patients', $patient, 'create');

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
        // Own-file rule: patient-role accounts open only their linked file.
        $this->authorize('view', $patient);

        if ($redirect = $this->patientFileTwoFactorRedirect($patient)) {
            return $redirect;
        }

        $encounters = $patient->encounters()->with(['vitals', 'prescriptions', 'facility'])->orderByDesc('encounter_date')->get();
        $admissions = $patient->admissions()->with('facility')->orderByDesc('admitted_at')->get();
        $guardian = $patient->guardian;
        $labOrders = \App\Models\LabOrder::where('patient_id', $patient->id)->latest('requested_at')->take(10)->get();
        
        return view('patients.show', compact('patient', 'encounters', 'admissions', 'guardian', 'labOrders'));
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

            AuditLog::create([
                'action' => 'update',
                'subject_type' => Patient::class,
                'subject_id' => $patient->id,
                'user_id' => auth()->id(),
                'description' => 'Patient information updated: ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ')',
            ]);

            SyncService::enqueue('patients', $patient, 'update');
            
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
        $this->authorize('view_patients');

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
        $this->authorize('view_patients');

        $request->validate([
            'dhp_id' => 'required|string|max:64',
        ]);

        $qrData = QrCodeService::parseQrCodeData($request->dhp_id);
        $dhpId = trim($qrData['dhp_id'] ?? $request->dhp_id);

        $patient = Patient::where('dhp_id', $dhpId)->first();

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
            'weight' => 'nullable|numeric|min:0|max:400',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'systolic_bp' => 'nullable|numeric|min:50|max:250',
            'diastolic_bp' => 'nullable|numeric|min:30|max:200',
            'heart_rate' => 'nullable|numeric|min:30|max:250',
            'respiratory_rate' => 'nullable|numeric|min:5|max:80',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'priority_level' => 'nullable|string|in:Emergency,High,Medium,Low',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (isset($validated['systolic_bp'], $validated['diastolic_bp'])
            && $validated['systolic_bp'] !== null && $validated['diastolic_bp'] !== null
            && $validated['systolic_bp'] < $validated['diastolic_bp']) {
            return redirect()->back()->with('error', 'Systolic pressure cannot be lower than diastolic pressure.')->withInput();
        }
        
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

            AuditLog::create([
                'action' => 'update',
                'subject_type' => Encounter::class,
                'subject_id' => $encounter->id,
                'user_id' => auth()->id(),
                'description' => 'Triage vitals recorded for patient ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') - priority: ' . ($existingVitals->priority_level ?? 'none'),
            ]);

            SyncService::enqueue('encounters', $encounter, 'update');
            SyncService::enqueue('vitals', $existingVitals, 'create');
            
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
        
        $inventory = Inventory::whereIn('status', ['available', 'low_stock'])->get();
        
        return view('patients.consultation', compact('patient', 'latestEncounter', 'inventory'));
    }

    /**
     * Save consultation data for patient
     */
    public function saveConsultation(Request $request)
    {
        $this->authorize('consult_patient');
        
        $patient = Patient::findOrFail($request->patient_id);
        
        $validated = $request->validate([
            'chief_complaint' => 'required|string|max:2000',
            'examination_findings' => 'nullable|string|max:5000',
            'diagnosis' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:5000',
            'requires_admission' => 'nullable|boolean',
            'prescriptions' => 'nullable|array|max:20',
            'prescriptions.*.medication_name' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.dose' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.frequency' => 'required_with:prescriptions|string|max:255',
            'prescriptions.*.quantity' => 'nullable|integer|min:1|max:10000',
            'prescriptions.*.duration' => 'nullable|string|max:255',
            'prescriptions.*.instructions' => 'nullable|string|max:2000',
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
                'examination_findings' => $validated['examination_findings'] ?? null,
                'diagnosis' => $validated['diagnosis'] ?? null,
                'treatment_plan' => $validated['treatment_plan'] ?? null,
                'requires_admission' => $validated['requires_admission'] ?? false,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create prescriptions issued during this consultation
            $createdPrescriptions = collect();
            if (!empty($validated['prescriptions'])) {
                foreach ($validated['prescriptions'] as $prescriptionData) {
                    if (empty($prescriptionData['medication_name'])) {
                        continue;
                    }

                    $createdPrescriptions->push($encounter->prescriptions()->create([
                        'patient_id' => $patient->id,
                        'prescribed_by_user_id' => $this->user()->id,
                        'medication_name' => $prescriptionData['medication_name'],
                        'dose' => $prescriptionData['dose'],
                        'frequency' => $prescriptionData['frequency'],
                        'quantity' => $prescriptionData['quantity'] ?? null,
                        'duration' => $prescriptionData['duration'] ?? null,
                        'instructions' => $prescriptionData['instructions'] ?? null,
                        'status' => 'pending',
                        'prescribed_at' => now(),
                    ]));
                }
            }
            
            DB::commit();

            AuditLog::create([
                'action' => 'update',
                'subject_type' => Encounter::class,
                'subject_id' => $encounter->id,
                'user_id' => auth()->id(),
                'description' => 'Consultation recorded for patient ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') - diagnosis: ' . ($validated['diagnosis'] ?? 'none') . ', admission: ' . (($validated['requires_admission'] ?? false) ? 'yes' : 'no'),
            ]);

            SyncService::enqueue('encounters', $encounter, 'update');
            foreach ($createdPrescriptions as $prescription) {
                SyncService::enqueue('prescriptions', $prescription, 'create');
            }
            
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
        $inventory = Inventory::all();
        
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

            $prescription = Prescription::where('id', $validated['prescription_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Ownership check: prescription must belong to this patient.
            if ((int) $prescription->patient_id !== (int) $patient->id) {
                throw new \Exception('This prescription does not belong to the selected patient.');
            }

            if ($prescription->status !== 'pending') {
                throw new \Exception('Only pending prescriptions can be dispensed');
            }

            $quantityDispensed = (int) ($validated['quantity_dispensed'] ?? $prescription->quantity ?? 1);
            if ($quantityDispensed < 1) {
                throw new \Exception('Dispensed quantity must be at least 1.');
            }

            // Facility-scoped stock check with row lock to prevent overselling.
            $inventoryItem = Inventory::where('medication_name', $prescription->medication_name)
                ->when($this->user()->isFacilityScoped(), fn ($q) => $q->where('facility_id', $this->user()->facility_id))
                ->lockForUpdate()
                ->first();

            if ($inventoryItem) {
                if ($inventoryItem->isExpired()) {
                    throw new \Exception("Cannot dispense '{$prescription->medication_name}': stock is expired.");
                }
                if ($inventoryItem->current_stock < $quantityDispensed) {
                    throw new \Exception("Insufficient stock for '{$prescription->medication_name}'. Available: {$inventoryItem->current_stock}.");
                }
                $inventoryItem->decrement('current_stock', $quantityDispensed);
                $inventoryItem->refresh();
                $inventoryItem->updateStatus();
            }

            // Mark prescription as dispensed only after stock is confirmed.
            $prescription->update([
                'status' => 'dispensed',
                'dispensed_at' => now(),
                'dispensed_by_user_id' => $this->user()->id,
                'quantity' => $quantityDispensed,
            ]);
            
            // Create dispensing record or log
            \Log::info('Medication dispensed', [
                'prescription_id' => $prescription->id,
                'patient_id' => $patient->id,
                'quantity_dispensed' => $validated['quantity_dispensed'] ?? null,
                'dispensed_by' => $this->user()->id,
            ]);
            
            DB::commit();

            AuditLog::create([
                'action' => 'update',
                'subject_type' => Prescription::class,
                'subject_id' => $prescription->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => 'Medication dispensed: ' . $prescription->medication_name . ' for patient ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') - quantity: ' . $quantityDispensed,
            ]);

            SyncService::enqueue('prescriptions', $prescription, 'update');
            
            return redirect()->route('patients.show', $patient)
                ->with('success', "Medication dispensed successfully. Stock updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Medication dispensing failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);

            $businessRules = ['Only pending prescriptions', 'does not belong', 'Insufficient stock', 'expired', 'at least 1'];
            $message = 'Failed to dispense medication. Please try again.';
            foreach ($businessRules as $rule) {
                if (str_contains($e->getMessage(), $rule)) {
                    $message = $e->getMessage();
                    break;
                }
            }
            
            return redirect()->back()->with('error', $message);
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
        
        // Prevent duplicate active admissions
        $existingActiveAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
        if ($existingActiveAdmission) {
            return redirect()->route('patients.show', $patient)
                          ->with('error', 'This patient already has an active admission. Discharge the patient before creating a new admission.');
        }
        
        $validated = $request->validate([
            'bed_number' => 'required|string|max:20',
            'ward' => 'required|string|max:100',
            'admission_type' => 'required|in:emergency,elective,urgent,transfer',
            'admission_reason' => 'nullable|string|max:2000',
        ]);

        // Bed occupancy guard: one active patient per bed per facility.
        $bedOccupied = Admission::where('status', 'active')
            ->where('bed_number', $validated['bed_number'])
            ->when($this->user()->isFacilityScoped(), fn ($q) => $q->where('facility_id', $this->user()->facility_id))
            ->exists();
        if ($bedOccupied) {
            return redirect()->back()->with('error', "Bed {$validated['bed_number']} is already occupied by another active admission.")->withInput();
        }
        
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
                'bed_number' => trim($validated['bed_number']),
                'ward_name' => trim($validated['ward']),
                'admission_type' => $validated['admission_type'],
                'admission_reason' => $validated['admission_reason'] ?? null,
                'admitted_at' => now(),
                'status' => 'active',
            ]);
            
            DB::commit();

            AuditLog::create([
                'action' => 'create',
                'subject_type' => Admission::class,
                'subject_id' => $admission->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => 'Patient admitted: ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') to ward ' . $validated['ward'] . ' Bed ' . $validated['bed_number'],
            ]);

            SyncService::enqueue('encounters', $encounter, 'create');
            SyncService::enqueue('admissions', $admission, 'create');
            
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
            'weight' => 'nullable|numeric|min:0|max:400',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (empty(array_filter($validated, fn ($v) => $v !== null && $v !== ''))) {
            return redirect()->back()->with('error', 'Enter at least one vital sign or note.')->withInput();
        }
        
        try {
            DB::beginTransaction();

            $activeAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
            if (!$activeAdmission) {
                throw new \Exception('Ward rounds require an active admission for this patient.');
            }
            
            // Get or create latest encounter
            $encounter = $patient->encounters()->latest()->first();
            if (!$encounter || in_array($encounter->status, ['completed'])) {
                $encounter = $patient->encounters()->create([
                    'encounter_type' => 'ward_round',
                    'facility_id' => $this->user()->facility_id,
                    'user_id' => $this->user()->id,
                    'encounter_date' => now(),
                    'status' => 'active',
                ]);
            }
            
            // Always append a new vital row — never overwrite history.
            $vital = $encounter->vitals()->create([
                'patient_id' => $patient->id,
                'temperature' => $validated['temperature'] ?? null,
                'heart_rate' => $validated['heart_rate'] ?? null,
                'respiratory_rate' => $validated['respiratory_rate'] ?? null,
                'oxygen_saturation' => $validated['oxygen_saturation'] ?? null,
                'systolic_bp' => $validated['blood_pressure_systolic'] ?? null,
                'diastolic_bp' => $validated['blood_pressure_diastolic'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'priority_level' => 'Low',
                'recorded_by_user_id' => $this->user()->id,
                'recorded_at' => now(),
            ]);
            $vital->update(['priority_level' => $vital->autoPriorityLevel()]);
            
            DB::commit();

            AuditLog::create([
                'action' => 'update',
                'subject_type' => Vital::class,
                'subject_id' => $vital->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => 'Ward round vitals recorded for patient ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') - temp: ' . $vital->temperature . ' HR: ' . $vital->heart_rate,
            ]);

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
     * Show ward dashboard for an admitted patient
     */
    public function ward(Patient $patient)
    {
        $this->authorize('update_patient');

        $latestAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
        if (!$latestAdmission) {
            $latestAdmission = $patient->admissions()->latest()->first();
        }

        $medicationAdministrations = $latestAdmission
            ? $latestAdmission->medicationAdministrations()->with('administeredByUser')->get()
            : collect();
        $progressNotes = $latestAdmission
            ? $latestAdmission->progressNotes()->with('recordedByUser')->get()
            : collect();

        // Active prescriptions for the med administration log
        $prescriptions = $patient->prescriptions()
            ->where('status', 'pending')
            ->orderByDesc('prescribed_at')
            ->get();

        return view('patients.ward', compact('patient', 'latestAdmission', 'medicationAdministrations', 'progressNotes', 'prescriptions'));
    }

    /**
     * Record medication administration
     */
    public function administerMedication(Request $request)
    {
        $this->authorize('update_patient');

        $patient = Patient::findOrFail($request->patient_id);

        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'dose' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:50',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'administered_at' => 'nullable|date|before_or_equal:now',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            DB::beginTransaction();

            if (!empty($validated['prescription_id'])) {
                $linked = Prescription::where('id', $validated['prescription_id'])
                    ->where('patient_id', $patient->id)->exists();
                if (!$linked) {
                    throw new \Exception('Linked prescription does not belong to this patient.');
                }
            }

            $latestAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
            if (!$latestAdmission) {
                throw new \Exception('Patient has no active admission');
            }

            $administration = $latestAdmission->medicationAdministrations()->create([
                'prescription_id' => $validated['prescription_id'] ?? null,
                'patient_id' => $patient->id,
                'administered_by_user_id' => $this->user()->id,
                'medication_name' => $validated['medication_name'],
                'dose' => $validated['dose'] ?? null,
                'route' => $validated['route'] ?? null,
                'administered_at' => $validated['administered_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            AuditLog::create([
                'action' => 'create',
                'subject_type' => 'MedicationAdministration',
                'subject_id' => $administration->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => "Medication '{$validated['medication_name']}' administered for patient {$patient->full_name} (DHP ID: {$patient->dhp_id})",
            ]);

            SyncService::enqueue('medication_administrations', $administration, 'create', $latestAdmission->facility_id);

            DB::commit();

            return redirect()->route('ward', $patient)
                ->with('success', "Medication '{$validated['medication_name']}' administered successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Medication administration failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record a daily progress note
     */
    public function saveProgressNote(Request $request)
    {
        $this->authorize('update_patient');

        $patient = Patient::findOrFail($request->patient_id);

        $validated = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        try {
            DB::beginTransaction();

            $latestAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
            if (!$latestAdmission) {
                throw new \Exception('Patient has no active admission');
            }

            $progressNote = $latestAdmission->progressNotes()->create([
                'patient_id' => $patient->id,
                'recorded_by_user_id' => $this->user()->id,
                'note' => $validated['note'],
                'recorded_at' => now(),
            ]);

            AuditLog::create([
                'action' => 'create',
                'subject_type' => 'ProgressNote',
                'subject_id' => $progressNote->id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => "Progress note recorded for patient {$patient->full_name} (DHP ID: {$patient->dhp_id})",
            ]);

            SyncService::enqueue('progress_notes', $progressNote, 'create', $latestAdmission->facility_id);

            DB::commit();

            return redirect()->route('ward', $patient)
                ->with('success', 'Progress note recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Progress note failed', [
                'error' => $e->getMessage(),
                'patient_id' => $patient->id,
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Discharge patient — show confirmation form (GET).
     */
    public function showDischargeForm(Patient $patient)
    {
        $this->authorize('discharge_patient');

        $activeAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
        if (!$activeAdmission) {
            return redirect()->route('patients.show', $patient)
                ->with('error', 'No active admission to discharge for this patient.');
        }

        return view('patients.discharge', compact('patient', 'activeAdmission'));
    }

    /**
     * Ward round — show vitals form (GET).
     */
    public function showWardRoundForm(Patient $patient)
    {
        $this->authorize('update_patient');

        $activeAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
        if (!$activeAdmission) {
            return redirect()->route('ward', $patient)
                ->with('error', 'Ward rounds require an active admission for this patient.');
        }

        return view('patients.ward_round', compact('patient', 'activeAdmission'));
    }

    /**
     * Discharge patient
     */
    public function dischargePatient(Request $request, Patient $patient)
    {
        $this->authorize('discharge_patient');
        
        $validated = $request->validate([
            'final_diagnosis' => 'required|string|max:2000',
            'discharge_status' => 'nullable|in:Improved,Not Improved,Referred,Left Against Medical Advice,Deceased',
            'follow_up_instructions' => 'nullable|string|max:2000',
            // "now" (not "today"): a discharge recorded this afternoon must
            // not fail validation for being later than midnight.
            'discharge_date' => 'nullable|date|before_or_equal:now|after:2000-01-01',
        ]);
        
        try {
            DB::beginTransaction();

            // Only an active admission can be discharged.
            $latestAdmission = $patient->admissions()->where('status', 'active')->latest()->first();
            if (!$latestAdmission) {
                return redirect()->route('patients.show', $patient)
                    ->with('error', 'No active admission to discharge for this patient.');
            }
            
            $latestAdmission->update([
                'discharged_at' => $validated['discharge_date'] ?? now(),
                'status' => 'discharged',
                'discharge_summary' => $validated['final_diagnosis'],
                'discharge_status' => $validated['discharge_status'] ?? 'Improved',
                'follow_up_instructions' => $validated['follow_up_instructions'] ?? null,
                'discharged_by_user_id' => $this->user()->id,
            ]);
            
            // Get latest encounter and mark as completed
            $latestEncounter = $patient->encounters()->latest()->first();
            if ($latestEncounter) {
                $latestEncounter->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
            
            DB::commit();

            $admissionId = $latestAdmission ? $latestAdmission->id : null;
            
            AuditLog::create([
                'action' => 'update',
                'subject_type' => $admissionId ? Admission::class : Encounter::class,
                'subject_id' => $admissionId,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'description' => 'Patient discharged: ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ') - diagnosis: ' . $validated['final_diagnosis'],
            ]);

            if ($latestAdmission) {
                SyncService::enqueue('admissions', $latestAdmission, 'update');
            }
            if ($latestEncounter) {
                SyncService::enqueue('encounters', $latestEncounter, 'update');
            }
            
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
        
        $syncQueue = SyncQueue::latest()->take(20)->get();
        $syncedCount = SyncQueue::where('status', 'synced')->count();
        $pendingCount = SyncQueue::where('status', 'pending')->count();
        $failedCount = SyncQueue::where('status', 'failed')->count();
        
        return view('patients.sync-status', compact('syncQueue', 'syncedCount', 'pendingCount', 'failedCount'));
    }

    /**
     * Upload sync data to national database
     */
    public function syncUpload(Request $request)
    {
        $this->authorize('upload_sync');
        
        $validated = $request->validate([
            'record_type' => 'required|string|in:patients,encounters,vitals,prescriptions,admissions,inventory,lab_orders,progress_notes,medication_administrations',
            'record_id' => 'required|integer|min:1',
            'data' => 'required|json|max:65535',
        ]);
        
        try {
            $syncQueue = SyncQueue::create([
                'facility_id' => $this->user()->facility_id,
                'record_type' => $validated['record_type'],
                'record_id' => $validated['record_id'],
                'action' => 'create',
                'payload' => json_decode($validated['data'], true),
                'status' => 'pending',
            ]);
            
            // Dispatch background job to push this record to the national database
            ProcessSyncQueue::dispatch(50);
            
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
        $this->authorize('view', $patient);

        if ($redirect = $this->patientFileTwoFactorRedirect($patient)) {
            return $redirect;
        }

        try {
            $qrCode = QrCodeService::generateQrCodeSvg($patient->dhp_id);

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
        $this->authorize('view', $patient);

        if ($redirect = $this->patientFileTwoFactorRedirect($patient)) {
            return $redirect;
        }

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

    /**
     * Retry a failed sync queue record
     */
    public function syncRetry(Request $request, $id)
    {
        $this->authorize('view_sync_queue');

        $syncQueue = SyncQueue::findOrFail($id);

        if (!$syncQueue->canRetry()) {
            return redirect()->back()->with('error', 'Sync record has exceeded the maximum retry count');
        }

        $syncQueue->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        ProcessSyncQueue::dispatch(50);

        return redirect()->back()->with('success', 'Sync record queued for retry');
    }
}
