<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
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

        $registeredQuery = Patient::whereBetween('registered_at', [$from, $to]);
        $allQuery = Patient::query();

        $census = [
            'total' => Patient::count(),
            'registeredInPeriod' => $registeredQuery->count(),
            'active' => Patient::where('status', 'active')->count(),
            'inactive' => Patient::where('status', 'inactive')->count(),
            'deceased' => Patient::where('status', 'deceased')->count(),
            'children' => Patient::where('is_child', true)->count(),
            'adults' => Patient::where('is_child', false)->count(),
        ];

        $byGender = $allQuery->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->orderByDesc('total')
            ->get();

        $byDistrict = Patient::select('district', DB::raw('count(*) as total'))
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

        $items = Inventory::orderBy('status')->orderBy('medication_name')->get();

        $stats = [
            'available' => Inventory::where('status', 'available')->count(),
            'lowStock' => Inventory::where('status', 'low_stock')->count(),
            'outOfStock' => Inventory::where('status', 'out_of_stock')->count(),
            'expired' => Inventory::where('status', 'expired')->count(),
        ];

        return view('reports.inventory', compact('items', 'stats'));
    }
}