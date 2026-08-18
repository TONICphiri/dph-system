<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $today = now()->toDateString();

        $stats = [
            'totalPatients' => Patient::count(),
            'patientsToday' => Patient::whereDate('registered_at', $today)->count(),
            'pendingTriage' => Encounter::where('status', 'registered')->count(),
            'awaitingConsultation' => Encounter::whereIn('status', ['triaged', 'active'])->count(),
            'admittedToday' => Admission::whereDate('admitted_at', $today)->count(),
            'activeAdmissions' => Admission::where('status', 'active')->count(),
            'lowStock' => Inventory::whereIn('status', ['low_stock', 'out_of_stock'])->count(),
        ];

        $recentPatients = Patient::latest('registered_at')->take(5)->get();
        $recentEncounters = Encounter::with('patient')->latest('encounter_date')->take(5)->get();

        return view('dashboard', compact('stats', 'recentPatients', 'recentEncounters'));
    }
}