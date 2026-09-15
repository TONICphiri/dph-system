<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\SyncQueue;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * National administration home. Only reachable by National Admins
 * (global read/write, no facility constraint): system-wide stats,
 * facility performance, sync health and the global audit trail.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('manage_facility');

        $today = now()->toDateString();

        $stats = [
            'totalFacilities' => Facility::count(),
            'activeFacilities' => Facility::where('status', 'active')->count(),
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'totalPatients' => Patient::count(),
            'patientsToday' => Patient::whereDate('registered_at', $today)->count(),
            'activeAdmissions' => Admission::where('status', 'active')->count(),
            'lowStockFacilities' => Inventory::whereIn('status', ['low_stock', 'out_of_stock'])
                ->distinct()->count('facility_id'),
            'syncPending' => SyncQueue::where('status', 'pending')->count(),
            'syncFailed' => SyncQueue::where('status', 'failed')->count(),
        ];

        $facilities = Facility::withCount([
                'users as total_staff',
                'patients as total_patients',
                'encounters as total_encounters',
            ])
            ->orderByDesc('total_encounters')
            ->take(8)
            ->get();

        $recentAudit = AuditLog::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'facilities', 'recentAudit'));
    }
}
