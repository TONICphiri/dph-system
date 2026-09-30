<?php

namespace App\Http\Controllers\Dhp\Issuer;

use App\Enums\CredentialStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dhp\RevokeCredentialRequest;
use App\Http\Requests\Dhp\StoreCredentialRequest;
use App\Http\Requests\Dhp\UpdateCredentialRequest;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\Facility;
use App\Services\DhpAuditLogger;
use App\Services\DhpIdentifierService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Issue, correct, revoke/replace and print verifiable credentials.
 * Test results live in test_details only and never enter QR content,
 * flash messages, audit metadata, print views or logs.
 */
class CredentialController extends Controller
{
    public function create(Request $request, Citizen $citizen): View
    {
        $this->authorize('create', Credential::class);

        $replacementOf = null;
        if ($request->filled('replace_of')) {
            $replacementOf = Credential::query()
                ->whereKey($request->integer('replace_of'))
                ->where('citizen_id', $citizen->id)
                ->whereIn('status', [CredentialStatus::Revoked, CredentialStatus::Superseded])
                ->with(['vaccinationDetail', 'testDetail'])
                ->first();
        }

        return view('dhp.issuer.credentials.form', [
            'citizen' => $citizen,
            'credential' => new Credential(['type' => $replacementOf?->type->value ?? 'vaccination']),
            'replacementOf' => $replacementOf,
            'facilities' => $this->facilityOptions($request->user()->facility_id),
            'defaultFacilityId' => $request->user()->facility_id,
            'method' => 'POST',
            'action' => route('dhp.issuer.credentials.store', $citizen),
        ]);
    }

    public function store(StoreCredentialRequest $request, Citizen $citizen, DhpIdentifierService $ids): RedirectResponse
    {
        $data = $request->validated();
        $facilityId = $request->user()->facility_id ?? ($data['facility_id'] ?? null);

        if ($facilityId === null || ! Facility::query()->whereKey($facilityId)->where('is_active', true)->exists()) {
            return back()->withInput()->with('error', 'Choose an active issuing facility.');
        }

        $credential = DB::transaction(function () use ($data, $citizen, $request, $ids, $facilityId) {
            $isLab = $data['type'] === 'lab_test';

            $credential = Credential::create([
                'credential_number' => $ids->nextCredentialNumber(),
                'citizen_id' => $citizen->id,
                'facility_id' => $facilityId,
                'type' => $data['type'],
                'status' => CredentialStatus::Active,
                'issue_date' => $data['issue_date'],
                // Lab credentials expire at valid_until; vaccinations carry no expiry.
                'expiry_date' => $isLab ? $data['valid_until'] : null,
                'qr_token' => $ids->newQrToken(),
                'issued_by' => $request->user()->id,
            ]);

            if ($isLab) {
                $credential->testDetail()->create([
                    'test_type' => $data['test_type'],
                    'sample_collection_date' => $data['sample_collection_date'],
                    'result_date' => $data['result_date'],
                    'result' => $data['result'],
                    'valid_until' => $data['valid_until'],
                ]);
            } else {
                $credential->vaccinationDetail()->create([
                    'vaccine_name' => $data['vaccine_name'],
                    'dose_number' => $data['dose_number'],
                    'administration_date' => $data['administration_date'],
                    'batch_number' => $data['batch_number'] ?? null,
                    'next_dose_date' => $data['next_dose_date'] ?? null,
                ]);
            }

            if (! empty($data['replace_of'])) {
                $old = Credential::query()
                    ->whereKey($data['replace_of'])
                    ->where('citizen_id', $citizen->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $old->update(['replaced_by_credential_id' => $credential->id]);

                DhpAuditLogger::log(
                    user: $request->user(),
                    action: 'credential_replaced',
                    entityType: 'credential',
                    entityId: $credential->id,
                    details: ['credential_type' => $credential->type->value, 'facility_id' => $facilityId],
                    ipAddress: $request->ip(),
                );
            }

            DhpAuditLogger::log(
                user: $request->user(),
                action: 'credential_issued',
                entityType: 'credential',
                entityId: $credential->id,
                details: [
                    'credential_type' => $credential->type->value,
                    'facility_id' => $facilityId,
                    'has_expiry' => $credential->expiry_date !== null,
                ],
                ipAddress: $request->ip(),
            );

            return $credential;
        });

        $credential->load('citizen.user');
        \App\Services\DhpNotificationService::queueCredentialIssued($credential, $request->user());

        return redirect()->route('dhp.issuer.credentials.print', $credential)
            ->with('success', 'Credential issued.');
    }

    public function edit(Credential $credential): View
    {
        $this->authorize('update', $credential);
        $this->requireActive($credential);
        $credential->load(['citizen', 'vaccinationDetail', 'testDetail']);

        return view('dhp.issuer.credentials.form', [
            'citizen' => $credential->citizen,
            'credential' => $credential,
            'replacementOf' => null,
            'facilities' => $this->facilityOptions(null),
            'defaultFacilityId' => $credential->facility_id,
            'method' => 'PUT',
            'action' => route('dhp.issuer.credentials.update', $credential),
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): RedirectResponse
    {
        if ($credential->status !== CredentialStatus::Active) {
            return back()->with('error', 'Only active credentials can be corrected.');
        }

        $data = $request->validated();
        $isLab = $credential->type->value === 'lab_test';

        DB::transaction(function () use ($data, $credential, $request, $isLab) {
            $changed = [];

            $detail = $isLab ? $credential->testDetail : $credential->vaccinationDetail;
            $before = $detail?->getAttributes() ?? [];

            if ($isLab) {
                $detail->fill([
                    'test_type' => $data['test_type'],
                    'sample_collection_date' => $data['sample_collection_date'],
                    'result_date' => $data['result_date'],
                    'result' => $data['result'],
                    'valid_until' => $data['valid_until'],
                ]);
            } else {
                $detail->fill([
                    'vaccine_name' => $data['vaccine_name'],
                    'dose_number' => $data['dose_number'],
                    'administration_date' => $data['administration_date'],
                    'batch_number' => $data['batch_number'] ?? null,
                    'next_dose_date' => $data['next_dose_date'] ?? null,
                ]);
            }

            foreach ($detail->getDirty() as $field => $_) {
                if (array_key_exists($field, $before)) {
                    $changed[] = $field;
                }
            }
            $detail->save();

            $expiryChanged = false;
            $updates = ['issue_date' => $data['issue_date']];
            if (! empty($data['facility_id'])) {
                $updates['facility_id'] = $data['facility_id'];
            }
            if ($isLab && $credential->expiry_date?->format('Y-m-d') !== $data['valid_until']) {
                $updates['expiry_date'] = $data['valid_until'];
                $expiryChanged = true;
            }
            foreach ($updates as $field => $value) {
                if ((string) $credential->getAttribute($field) !== (string) $value) {
                    $changed[] = $field;
                }
            }
            $credential->update($updates);

            DhpAuditLogger::log(
                user: $request->user(),
                action: 'credential_updated',
                entityType: 'credential',
                entityId: $credential->id,
                details: [
                    'credential_type' => $credential->type->value,
                    'changed_fields' => array_values(array_unique($changed)),
                    'expiry_changed' => $expiryChanged,
                ],
                ipAddress: $request->ip(),
            );
        });

        return redirect()->route('dhp.issuer.credentials.print', $credential)
            ->with('success', 'Credential corrected.');
    }

    public function revokeForm(Credential $credential): View
    {
        $this->authorize('revoke', $credential);
        $this->requireActive($credential);
        $credential->load('citizen');

        return view('dhp.issuer.credentials.revoke', [
            'credential' => $credential,
            'reasons' => RevokeCredentialRequest::REASONS,
        ]);
    }

    public function revoke(RevokeCredentialRequest $request, Credential $credential): RedirectResponse
    {
        if ($credential->status !== CredentialStatus::Active) {
            return back()->with('error', 'Only active credentials can be revoked.');
        }

        $data = $request->validated();
        $wantsReplace = (bool) ($data['replace'] ?? false);

        // Fraud keeps a revoked marker; administrative corrections supersede.
        $newStatus = $wantsReplace && $data['reason'] !== 'suspected_fraud'
            ? CredentialStatus::Superseded
            : CredentialStatus::Revoked;

        DB::transaction(function () use ($data, $credential, $request, $newStatus, $wantsReplace) {
            $credential->update([
                'status' => $newStatus,
                'revoked_at' => now(),
                'revoked_by' => $request->user()->id,
                // Free text stays in the record only, never in audit/logs/output.
                'revocation_reason' => $data['reason'].(! empty($data['reason_note']) ? ': '.$data['reason_note'] : ''),
            ]);

            DhpAuditLogger::log(
                user: $request->user(),
                action: 'credential_revoked',
                entityType: 'credential',
                entityId: $credential->id,
                details: [
                    'credential_type' => $credential->type->value,
                    'reason_category' => $data['reason'],
                    'replaced' => $wantsReplace,
                ],
                ipAddress: $request->ip(),
            );
        });

        if ($wantsReplace) {
            $credential->load('citizen.user');
            \App\Services\DhpNotificationService::queueCredentialRevoked($credential, $data['reason'], true, $request->user());

            return redirect()->route('dhp.issuer.credentials.create', ['citizen' => $credential->citizen_id, 'replace_of' => $credential->id])
                ->with('success', 'Credential revoked. Review the replacement below and submit it.');
        }

        $credential->load('citizen.user');
        \App\Services\DhpNotificationService::queueCredentialRevoked($credential, $data['reason'], false, $request->user());

        // Server-side session confirmation is short-lived; revoke it with the credential.
        \App\Services\DhpIdentityConfirmation::revoke($credential->citizen_id);

        return redirect()->route('dhp.issuer.citizens.show', $credential->citizen_id)
            ->with('success', 'Credential revoked.');
    }

    public function print(Credential $credential, QrCodeService $qr): View
    {
        $this->authorize('view', $credential);
        $credential->load(['citizen', 'facility']);

        return view('dhp.issuer.credentials.print', [
            'credential' => $credential,
            'qrCode' => $qr->forCredential($credential, 180),
            'verifyUrl' => url('/verify/by-token/'.$credential->qr_token),
        ]);
    }

    private function requireActive(Credential $credential): void
    {
        abort_if($credential->status !== CredentialStatus::Active, 403, 'Only active credentials can be changed.');
    }

    /**
     * @return array<string, string> id => name of active facilities.
     */
    private function facilityOptions(?int $preselect): array
    {
        return Facility::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }
}
