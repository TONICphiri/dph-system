<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Facility scope for aggregated analytics. National Admins get
     * nationwide dashboards (null = no constraint); Facility Admins
     * get facility-specific reports only.
     */
    protected function scopedFacilityId(): ?int
    {
        $me = $this->user();

        return $me->isNationalAdmin() ? null : (int) $me->facility_id;
    }

    /**
     * Display the reports dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('view_reports');

        return view('reports.index');
    }

    /**
     * Patient census report
     */
    public function census(Request $request)
    {
        $this->authorize('view_reports');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();

        $facilityId = $this->scopedFacilityId();
        $forFacility = fn ($q) => $facilityId ? $q->where('registered_by_facility_id', $facilityId) : $q;

        $registeredQuery = $forFacility(Patient::whereBetween('registered_at', [$from, $to]));
        $allQuery = $forFacility(Patient::query());

        $census = [
            'total' => (clone $allQuery)->count(),
            'registeredInPeriod' => (clone $registeredQuery)->count(),
            'active' => (clone $allQuery)->where('status', 'active')->count(),
            'inactive' => (clone $allQuery)->where('status', 'inactive')->count(),
            'deceased' => (clone $allQuery)->where('status', 'deceased')->count(),
            'children' => (clone $allQuery)->where('is_child', true)->count(),
            'adults' => (clone $allQuery)->where('is_child', false)->count(),
        ];

        $byGender = $allQuery->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->orderByDesc('total')
            ->get();

        $byDistrict = $forFacility(Patient::select('district', DB::raw('count(*) as total')))
            ->whereNotNull('district')
            ->groupBy('district')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $registeredInPeriod = $registeredQuery->latest('registered_at')->take(20)->get();

        return view('reports.census', compact('census', 'byGender', 'byDistrict', 'registeredInPeriod', 'from', 'to'));
    }

    /**
     * OPD visits report
     */
    public function opdVisits(Request $request)
    {
        $this->authorize('view_reports');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();

        $visits = Encounter::whereBetween('encounter_date', [$from, $to]);

        if ($facilityId = $this->scopedFacilityId()) {
            $visits->where('facility_id', $facilityId);
        }

        $totals = [
            'total' => (clone $visits)->count(),
            'completed' => (clone $visits)->where('status', 'completed')->count(),
            'triaged' => (clone $visits)->whereIn('status', ['triaged', 'consultation'])->count(),
            'admitted' => (clone $visits)->where('status', 'admitted')->count(),
        ];

        $byType = (clone $visits)->select('encounter_type', DB::raw('count(*) as total'))
            ->groupBy('encounter_type')
            ->orderByDesc('total')
            ->get();

        $daily = (clone $visits)->select(
                DB::raw('date(encounter_date) as day'),
                DB::raw('count(*) as total')
            )
            ->groupBy(DB::raw('date(encounter_date)'))
            ->orderBy(DB::raw('date(encounter_date)'))
            ->get();

        $recentVisits = (clone $visits)->with('patient')->latest('encounter_date')->take(20)->get();

        return view('reports.opd-visits', compact('totals', 'byType', 'daily', 'recentVisits', 'from', 'to'));
    }

    /**
     * Admissions report
     */
    public function admissions(Request $request)
    {
        $this->authorize('view_reports');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();

        $admissions = Admission::whereBetween('admitted_at', [$from, $to]);

        if ($facilityId = $this->scopedFacilityId()) {
            $admissions->where('facility_id', $facilityId);
        }

        $totals = [
            'total' => (clone $admissions)->count(),
            'active' => (clone $admissions)->where('status', 'active')->count(),
            'discharged' => (clone $admissions)->where('status', 'discharged')->count(),
            'transferred' => (clone $admissions)->where('status', 'transferred')->count(),
        ];

        $byWard = (clone $admissions)->select('ward_name', DB::raw('count(*) as total'))
            ->whereNotNull('ward_name')
            ->groupBy('ward_name')
            ->orderByDesc('total')
            ->get();

        $byType = (clone $admissions)->select('admission_type', DB::raw('count(*) as total'))
            ->groupBy('admission_type')
            ->orderByDesc('total')
            ->get();

        // Average length of stay (days) for discharged admissions in period
        $avgStay = (clone $admissions)->whereNotNull('discharged_at')
            ->get()
            ->map(fn ($admission) => $admission->admitted_at->diffInDays($admission->discharged_at))
            ->avg();

        $recentAdmissions = (clone $admissions)->with('patient')->latest('admitted_at')->take(20)->get();

        return view('reports.admissions', compact('totals', 'byWard', 'byType', 'avgStay', 'recentAdmissions', 'from', 'to'));
    }

    /**
     * Dispensed medications report
     */
    public function dispensedMeds(Request $request)
    {
        $this->authorize('view_reports');

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();

        $dispensed = Prescription::where('status', 'dispensed')
            ->whereBetween('dispensed_at', [$from, $to]);

        if ($facilityId = $this->scopedFacilityId()) {
            $dispensed->whereHas('encounter', fn ($q) => $q->where('facility_id', $facilityId));
        }

        $totals = [
            'total' => (clone $dispensed)->count(),
            'totalQuantity' => (clone $dispensed)->sum('quantity'),
        ];

        $byMedication = (clone $dispensed)->select(
                'medication_name',
                DB::raw('count(*) as prescriptions'),
                DB::raw('sum(quantity) as total_quantity')
            )
            ->groupBy('medication_name')
            ->orderByDesc('total_quantity')
            ->get();

        $recentDispensed = (clone $dispensed)->with('patient')->latest('dispensed_at')->take(20)->get();

        return view('reports.dispensed-meds', compact('totals', 'byMedication', 'recentDispensed', 'from', 'to'));
    }

    /**
     * Inventory report
     */
    public function inventory(Request $request)
    {
        $this->authorize('view_reports');

        $me = $this->user();
        $items = $me->scopeToFacility(Inventory::orderBy('status')->orderBy('medication_name'))->get();

        $stat = fn () => $me->scopeToFacility(Inventory::query());
        $stats = [
            'available' => $stat()->where('status', 'available')->count(),
            'lowStock' => $stat()->where('status', 'low_stock')->count(),
            'outOfStock' => $stat()->where('status', 'out_of_stock')->count(),
            'expired' => $stat()->where('status', 'expired')->count(),
        ];

        return view('reports.inventory', compact('items', 'stats'));
    }

    /**
     * Medical travel clearance report form
     */
    public function medicalClearance(Request $request)
    {
        $this->authorize('view_reports');

        $patient = null;
        $identifier = trim((string) $request->query('identifier'));

        if ($identifier !== '') {
            $patient = $this->findPatientByIdentifier($identifier);
        }

        $latestEncounter = $patient?->encounters()->with('user')->latest('encounter_date')->first();
        $activePrescriptions = $patient?->prescriptions()
            ->whereIn('status', ['pending', 'dispensed'])
            ->latest('prescribed_at')
            ->get() ?? collect();

        return view('reports.medical-clearance', compact('patient', 'identifier', 'latestEncounter', 'activePrescriptions'));
    }

    /**
     * Generate medical travel clearance PDF
     */
    public function medicalClearancePdf(Request $request)
    {
        $this->authorize('view_reports');

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'current_condition' => ['required', 'string', 'max:2000'],
            'treatment_plan' => ['required', 'string', 'max:2000'],
            'medications' => ['nullable', 'string', 'max:4000'],
            'medical_equipment' => ['nullable', 'string', 'max:2000'],
            'travel_clearance' => ['required', 'string', 'max:2000'],
            'flight_accommodations' => ['nullable', 'string', 'max:2000'],
            'physician_name' => ['required', 'string', 'max:255'],
            'physician_contact' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
        ]);

        $patient = $this->findPatientByIdentifier($validated['identifier']);

        if (!$patient) {
            return redirect()->route('reports.medical-clearance', ['identifier' => $validated['identifier']])
                ->withErrors(['identifier' => 'No patient was found with this National ID, DHP ID, or scanned QR code.'])
                ->withInput();
        }

        $patient->load(['registeredByFacility', 'prescriptions' => function ($query) {
            $query->whereIn('status', ['pending', 'dispensed'])->latest('prescribed_at');
        }]);

        $latestEncounter = $patient->encounters()->with('facility', 'user')->latest('encounter_date')->first();
        $facility = $latestEncounter?->facility ?? $patient->registeredByFacility ?? $request->user()->facility;

        $pdf = Pdf::loadView('reports.pdf.medical-clearance', [
            'patient' => $patient,
            'latestEncounter' => $latestEncounter,
            'facility' => $facility,
            'report' => $validated,
            'generatedBy' => $request->user(),
        ])->setPaper('a4');

        $fileName = 'medical-clearance-' . $patient->dhp_id . '-' . now()->format('YmdHis') . '.pdf';

        return $pdf->download($fileName);
    }

    private function findPatientByIdentifier(string $identifier): ?Patient
    {
        $qrData = QrCodeService::parseQrCodeData($identifier);
        $lookup = trim($qrData['dhp_id'] ?? $identifier);

        return Patient::where('dhp_id', $lookup)
            ->orWhere('national_id', $lookup)
            ->first();
    }
}
