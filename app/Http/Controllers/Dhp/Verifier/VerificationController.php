<?php

namespace App\Http\Controllers\Dhp\Verifier;

use App\Enums\VerificationMethod;
use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\DhpAuditLogger;
use App\Services\DhpCredentialVerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Authenticated verifier portal. Reuses the shared verification service;
 * only the disclosure level differs from the public flow.
 */
class VerificationController extends Controller
{
    public function __construct(private readonly DhpCredentialVerificationService $checks) {}

    public function verify(): View
    {
        return view('dhp.verifier.verify');
    }

    public function byToken(string $token, Request $request): View
    {
        $outcome = $this->checks->checkByToken($token);
        $this->record($outcome, VerificationMethod::QrScan, $request);

        return view('dhp.verifier.result', [
            'result' => $outcome['result'],
            'credential' => $outcome['credential'],
            'verifiedAt' => now(),
        ]);
    }

    public function byNumber(Request $request): View
    {
        $data = $request->validate([
            'credential_number' => ['required', 'string', 'max:30'],
        ]);

        $outcome = $this->checks->checkByNumber($data['credential_number']);
        $this->record($outcome, VerificationMethod::ManualCode, $request);

        return view('dhp.verifier.result', [
            'result' => $outcome['result'],
            'credential' => $outcome['credential'],
            'verifiedAt' => now(),
        ]);
    }

    /**
     * @param  array{result: mixed, credential: mixed}  $outcome
     */
    private function record(array $outcome, VerificationMethod $method, Request $request): void
    {
        Verification::create([
            'credential_id' => $outcome['credential']?->id,
            'verifier_id' => $request->user()->id,
            'method' => $method,
            'result' => $outcome['result'],
            'verified_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'credential_verified',
            entityType: 'credential',
            entityId: $outcome['credential']?->id,
            details: [
                'method' => $method->value,
                'result' => $outcome['result']->value,
                'authenticated_verifier' => true,
            ],
            ipAddress: $request->ip(),
        );
    }
}
