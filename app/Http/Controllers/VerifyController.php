<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use Illuminate\Http\Request;

/**
 * Public + staff verifier portal per system-description2.md FR-E1..E3, §6.4.
 * Returns only verification-level data, never the full medical record.
 */
class VerifyController extends Controller
{
    public function scan()
    {
        return view('verify.scan', ['result' => null]);
    }

    public function check(Request $request)
    {
        $request->validate(['credential' => ['required', 'string', 'max:8000']]);

        $result = VerificationService::verify(
            $request->input('credential'),
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            // Data-minimal response (FR-E1).
            return response()->json($result);
        }

        return view('verify.scan', ['result' => $result]);
    }

    public function history(Request $request)
    {
        $logs = \DB::table('verification_logs')->latest('scanned_at');

        if ($request->user() && method_exists($request->user(), 'isNationalAdmin') && ! $request->user()->isNationalAdmin()) {
            $logs->where('verifier_user_id', $request->user()->id);
        }

        return view('verify.history', ['logs' => $logs->paginate(20)]);
    }
}
