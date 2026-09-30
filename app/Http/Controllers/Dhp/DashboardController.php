<?php

namespace App\Http\Controllers\Dhp;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Digital Health Passport dashboards (Phase 2 placeholders).
 * Each page states what its phase will deliver; no issuance,
 * verification or administration flows live here yet.
 */
class DashboardController extends Controller
{
    public function citizen(): View
    {
        return view('dhp.citizen-dashboard', [
            'citizen' => request()->user()->citizenProfile,
        ]);
    }

    public function issuer(): View
    {
        return view('dhp.issuer-dashboard');
    }

    public function verifier(): View
    {
        return view('dhp.verifier-dashboard');
    }

    public function admin(): View
    {
        $usersByRole = \App\Models\User::query()
            ->where('is_active', true)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->all();

        $credentials = \App\Models\Credential::query()->get();
        $credentialCounts = ['active' => 0, 'expired' => 0, 'revoked' => 0, 'superseded' => 0];
        $expiringSoon = 0;
        foreach ($credentials as $credential) {
            $status = $credential->effective_status->value;
            $credentialCounts[$status] = ($credentialCounts[$status] ?? 0) + 1;

            if ($credential->expiry_date !== null
                && $credential->expiry_date->isFuture()
                && $credential->expiry_date->diffInDays(now()) <= 7) {
                $expiringSoon++;
            }
        }

        $lastExpiryJob = \App\Models\AuditLog::query()
            ->where('action', 'credentials_expiry_job_completed')
            ->latest('id')
            ->first();

        return view('dhp.admin-dashboard', [
            'usersByRole' => $usersByRole,
            'activeFacilities' => \App\Models\Facility::query()->where('is_active', true)->count(),
            'credentialCounts' => $credentialCounts,
            'verificationsToday' => \App\Models\Verification::query()->whereDate('verified_at', today())->count(),
            'verificationsWeek' => \App\Models\Verification::query()->where('verified_at', '>=', now()->subDays(7))->count(),
            'recentAudits' => \App\Models\AuditLog::query()->with('user')->latest('id')->limit(10)->get(),
            'expiringSoon' => $expiringSoon,
            'lastExpiryJob' => $lastExpiryJob,
        ]);
    }
}
