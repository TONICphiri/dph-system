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

        // Clinical consultation queue: encounters awaiting consultation,
        // ordered by vital priority (Emergency first) then by encounter date
        $consultationQueue = Encounter::with(['patient', 'vitals' => fn ($q) => $q->latest('recorded_at')])
            ->whereIn('status', ['triaged', 'consultation'])
            ->get()
            ->sort(function ($a, $b) {
                $priorityOrder = \App\Models\Vital::PRIORITY_ORDER;
                $aWeight = $a->vitals->first()?->priority_weight ?? count($priorityOrder);
                $bWeight = $b->vitals->first()?->priority_weight ?? count($priorityOrder);
                if ($aWeight !== $bWeight) {
                    return $aWeight <=> $bWeight;
                }
                return $a->encounter_date->timestamp <=> $b->encounter_date->timestamp;
            })
            ->values();

        return view('dashboard', compact('stats', 'recentPatients', 'recentEncounters', 'consultationQueue'));
    }
}