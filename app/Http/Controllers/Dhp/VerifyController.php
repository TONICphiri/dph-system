<?php

namespace App\Http\Controllers\Dhp;

use App\Enums\VerificationMethod;
use App\Enums\VerificationResult;
use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\DhpAuditLogger;
use App\Services\DhpCredentialVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public credential verification. Discloses the minimum necessary result;
 * full identity and medical detail never leave the server here.
 */
class VerifyController extends Controller
{
    public function __construct(private readonly DhpCredentialVerificationService $checks) {}

    public function index(): View
    {
        return view('dhp.verify.index');
    }

    public function byToken(string $token, Request $request): View
    {
        $outcome = $this->checks->checkByToken($token);
        $this->record($outcome, VerificationMethod::QrScan, $request);

        return view('dhp.verify.result', [
            'result' => $outcome['result'],
            'credential' => $outcome['credential'],
            'verifiedAt' => now(),
        ]);
    }

    public function byNumber(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'credential_number' => ['required', 'string', 'max:30'],
        ]);

        $outcome = $this->checks->checkByNumber($data['credential_number']);
        $this->record($outcome, VerificationMethod::ManualCode, $request);

        return view('dhp.verify.result', [
            'result' => $outcome['result'],
            'credential' => $outcome['credential'],
            'verifiedAt' => now(),
        ]);
    }

    /**
     * @param  array{result: VerificationResult, credential: mixed}  $outcome
     */
    private function record(array $outcome, VerificationMethod $method, Request $request): void
    {
        Verification::create([
            'credential_id' => $outcome['credential']?->id,
            'verifier_id' => auth()->id(),
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
                'authenticated_verifier' => auth()->check(),
            ],
            ipAddress: $request->ip(),
        );
    }
}
