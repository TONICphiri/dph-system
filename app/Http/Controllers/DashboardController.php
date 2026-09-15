<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\SyncQueue;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $today = now()->toDateString();
        $me = $this->user();
        // Facility Admins see their own facility's operations only.
        // National Admins see the nationwide aggregates.
        $facilityId = $me->isNationalAdmin() ? null : (int) $me->facility_id;

        $patients = $facilityId ? Patient::where('registered_by_facility_id', $facilityId) : Patient::query();
        $encounters = $facilityId ? Encounter::where('facility_id', $facilityId) : Encounter::query();
        $admissions = $facilityId ? Admission::where('facility_id', $facilityId) : Admission::query();
        $stock = $facilityId ? Inventory::where('facility_id', $facilityId) : Inventory::query();
        $sync = $facilityId ? SyncQueue::where('facility_id', $facilityId) : SyncQueue::query();

        $stats = [
            'totalPatients' => (clone $patients)->count(),
            'patientsToday' => (clone $patients)->whereDate('registered_at', $today)->count(),
            'pendingTriage' => (clone $encounters)->where('status', 'registered')->count(),
            'awaitingConsultation' => (clone $encounters)->whereIn('status', ['triaged', 'active'])->count(),
            'admittedToday' => (clone $admissions)->whereDate('admitted_at', $today)->count(),
            'activeAdmissions' => (clone $admissions)->where('status', 'active')->count(),
            'lowStock' => (clone $stock)->whereIn('status', ['low_stock', 'out_of_stock'])->count(),
        ];

        $recentPatients = (clone $patients)->latest('registered_at')->take(5)->get();
        $recentEncounters = (clone $encounters)->with('patient')->latest('encounter_date')->take(5)->get();

        // Sync status widget: pending/failed counts and connection status
        $syncStats = [
            'pending' => (clone $sync)->where('status', 'pending')->count(),
            'failed' => (clone $sync)->where('status', 'failed')->count(),
            'synced' => (clone $sync)->where('status', 'synced')->count(),
            'online' => !empty(config('services.sync.endpoint')),
        ];

        // Clinical consultation queue: encounters awaiting consultation,
        // ordered by vital priority (Emergency first) then by encounter date
        $consultationQueue = (clone $encounters)->with(['patient', 'vitals' => fn ($q) => $q->latest('recorded_at')])
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

        return view('dashboard', compact('stats', 'recentPatients', 'recentEncounters', 'consultationQueue', 'syncStats'));
    }
}