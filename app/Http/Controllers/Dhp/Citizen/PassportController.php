<?php

namespace App\Http\Controllers\Dhp\Citizen;

use App\Enums\CredentialStatus;
use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\DhpAuditLogger;
use App\Services\QrCodeService;
use Illuminate\View\View;

/**
 * Read-only citizen passport. Every credential page enforces
 * CredentialPolicy ownership; guessing another citizen's ID gives 403,
 * never a redirect that would leak existence.
 */
class PassportController extends Controller
{
    public function dashboard(QrCodeService $qr): View
    {
        $citizen = request()->user()->citizenProfile;

        $credentials = $citizen
            ? Credential::query()
                ->where('citizen_id', $citizen->id)
                ->with('facility')
                ->latest('issue_date')
                ->get()
            : collect();

        // Server-rendered QR images for active credentials only.
        $qrCodes = [];
        foreach ($credentials as $credential) {
            if ($credential->effective_status === CredentialStatus::Active) {
                $qrCodes[$credential->id] = $qr->forCredential($credential, 160);
            }
        }

        return view('dhp.citizen-dashboard', [
            'citizen' => $citizen,
            'credentials' => $credentials,
            'grouped' => $credentials->groupBy(fn (Credential $c) => $c->effective_status->value),
            'qrCodes' => $qrCodes,
        ]);
    }

    public function show(Credential $credential, QrCodeService $qr): View
    {
        $this->authorize('view', $credential);
        $credential->load(['citizen', 'facility', 'vaccinationDetail']);

        DhpAuditLogger::log(
            user: request()->user(),
            action: 'citizen_credential_viewed',
            entityType: 'credential',
            entityId: $credential->id,
            details: [
                'credential_type' => $credential->type->value,
                'credential_status' => $credential->effective_status->value,
            ],
            ipAddress: request()->ip(),
        );

        $active = $credential->effective_status === CredentialStatus::Active;

        return view('dhp.citizen.credentials.show', [
            'credential' => $credential,
            'qrCode' => $active ? $qr->forCredential($credential, 160) : null,
        ]);
    }

    public function print(Credential $credential, QrCodeService $qr): View
    {
        $this->authorize('view', $credential);
        $credential->load(['citizen', 'facility']);

        DhpAuditLogger::log(
            user: request()->user(),
            action: 'citizen_certificate_printed',
            entityType: 'credential',
            entityId: $credential->id,
            details: [
                'credential_type' => $credential->type->value,
                'credential_status' => $credential->effective_status->value,
            ],
            ipAddress: request()->ip(),
        );

        $active = $credential->effective_status === CredentialStatus::Active;

        return view('dhp.citizen.credentials.print', [
            'credential' => $credential,
            'qrCode' => $active ? $qr->forCredential($credential, 180) : null,
        ]);
    }
}
