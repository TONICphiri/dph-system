<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Encounter;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabOrderController extends Controller
{
    /**
     * Display a listing of lab orders.
     */
    public function index(Request $request)
    {
        $this->authorize('view_reports');

        $query = LabOrder::with(['patient', 'encounter', 'requestedBy']);

        // Filter by patient
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by test type
        if ($request->filled('test_type')) {
            $query->where('test_type', $request->input('test_type'));
        }

        $labOrders = $query->latest('requested_at')->paginate(15);

        return view('reports.lab-orders', compact('labOrders'));
    }

    /**
     * Show the form for creating a new lab order.
     */
    public function create(Patient $patient)
    {
        $this->authorize('create_patient');

        $latestEncounter = $patient->encounters()->latest()->first();

        return view('patients.lab-order-create', compact('patient', 'latestEncounter'));
    }

    /**
     * Store a newly created lab order.
     */
    public function store(StoreLabOrderRequest $request)
    {
        $this->authorize('create_lab_orders');

        $patient = Patient::findOrFail($request->patient_id);
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // If encounter_id is not provided, get the latest encounter for the patient
            $encounterId = $validated['encounter_id'] ?? null;
            if (!$encounterId) {
                $encounter = $patient->encounters()->latest()->first();
                $encounterId = $encounter ? $encounter->id : null;
            }

            $labOrder = LabOrder::create([
                'patient_id' => $patient->id,
                'encounter_id' => $encounterId,
                'test_type' => $validated['test_type'],
                'test_name' => $validated['test_name'],
                'status' => 'requested',
                'description' => $validated['description'] ?? null,
                'requested_by_user_id' => auth()->id(),
            ]);

            // Log the lab order creation
            AuditLog::create([
                'action' => 'create',
                'subject_type' => LabOrder::class,
                'subject_id' => $labOrder->id,
                'user_id' => auth()->id(),
                'description' => 'Lab order created: ' . $labOrder->test_name . ' for patient ' . $patient->full_name . ' (DHP ID: ' . $patient->dhp_id . ')',
            ]);

            DB::commit();

            return redirect()->route('patients.show', $patient)
                ->with('success', "Lab order created: {$labOrder->test_name} for {$patient->full_name}. DHP ID: {$patient->dhp_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lab order creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to create lab order. Please try again.');
        }
    }

    /**
     * Display the specified lab order.
     */
    public function show(LabOrder $labOrder)
    {
        $this->authorize('view_reports');

        $labOrder->load(['patient', 'encounter', 'requestedBy']);

        return view('reports.lab-order-show', compact('labOrder'));
    }

    /**
     * Update lab order results.
     */
    public function updateResults(UpdateLabOrderRequest $request, LabOrder $labOrder)
    {
        $this->authorize('record_lab_results');

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $labOrder->update([
                'status' => 'results',
                'result_value' => $validated['result_value'],
                'result_units' => $validated['result_units'] ?? null,
                'result_description' => $validated['result_description'] ?? null,
                'completed_at' => now(),
            ]);

            // Log the results update
            AuditLog::create([
                'action' => 'update',
                'subject_type' => LabOrder::class,
                'subject_id' => $labOrder->id,
                'user_id' => auth()->id(),
                'description' => 'Lab results recorded for ' . $labOrder->test_name . ' for patient ' . $labOrder->patient->full_name . ' (DHP ID: ' . $labOrder->patient->dhp_id . ') - Result: ' . $validated['result_value'],
            ]);

            DB::commit();

            return redirect()->route('lab.orders.show', $labOrder)
                ->with('success', "Lab results recorded for {$labOrder->test_name}. Patient: {$labOrder->patient->full_name}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lab results update failed', ['error' => $e->getMessage(), 'lab_order_id' => $labOrder->id]);

            return redirect()->back()->with('error', 'Failed to update lab results. Please try again.');
        }
    }

    /**
     * Show lab orders for a specific patient.
     */
    public function patientOrders(Patient $patient)
    {
        $this->authorize('view_reports');

        $labOrders = LabOrder::where('patient_id', $patient->id)
            ->with(['encounter', 'requestedBy'])
            ->latest('requested_at')
            ->paginate(15);

        return view('patients.lab-orders-patient', compact('patient', 'labOrders'));
    }
}