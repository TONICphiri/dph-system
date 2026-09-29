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
        return view('dhp.admin-dashboard');
    }
}
