<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\CredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Patient passport: QR credential manage + appointments + consent
 * per system-description2.md FR-B2..B5, FR-C1, §5.4.
 */
class PassportController extends Controller
{
    public function credential(Request $request)
    {
        $user = $request->user();

        $credentials = DB::table('qr_credentials')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('patient.credential', ['credentials' => $credentials]);
    }

    /**
     * Own medical details (FR-B1): the clinical file linked to this
     * account. Route is guarded by 2FA; ownership by users.patient_id.
     */
    public function records(Request $request)
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            return view('patient.records', [
                'patient' => null,
                'encounters' => collect(),
                'admissions' => collect(),
                'labOrders' => collect(),
            ]);
        }

        return view('patient.records', [
            'patient' => $patient,
            'encounters' => $patient->encounters()->with(['vitals', 'prescriptions', 'facility'])->get(),
            'admissions' => $patient->admissions()->with('facility')->get(),
            'labOrders' => $patient->labOrders()->latest('requested_at')->take(20)->get(),
        ]);
    }

    public function issueCredential(Request $request)
    {
        $issued = CredentialService::issue($request->user());

        return back()->with('success', 'New QR credential issued. Expires '.$issued['expires_at'].'.');
    }

    public function revokeCredential(Request $request, int $id)
    {
        $row = DB::table('qr_credentials')->where('id', $id)->first();
        abort_unless($row && (int) $row->user_id === (int) $request->user()->id, 403);

        CredentialService::revoke($id, $request->user());

        return back()->with('success', 'Credential revoked. Old QR codes will now show INVALID.');
    }

    public function appointments(Request $request)
    {
        $rows = DB::table('appointments')
            ->where('patient_user_id', $request->user()->id)
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return view('patient.appointments', ['appointments' => $rows]);
    }

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('appointments')->insert([
            'patient_user_id' => $request->user()->id,
            'facility_id' => $validated['facility_id'] ?? $request->user()->facility_id,
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'booked',
            'reason' => $validated['reason'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditService::log($request->user(), 'appointment.booked', $request->user(), []);

        return back()->with('success', 'Appointment booked.');
    }

    /** Consent grant/revoke so practitioners view records only with consent (FR-C1, NFR-7). */
    public function consentIndex(Request $request)
    {
        $grants = DB::table('consents')
            ->where('patient_user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('patient.consents', ['grants' => $grants]);
    }

    public function consentStore(Request $request)
    {
        $validated = $request->validate([
            'grantee_nin' => ['required', 'string'],
            'scope' => ['nullable', 'in:full_record,limited'],
        ]);

        $grantee = \App\Services\NinLookupService::findByNin($validated['grantee_nin']);
        abort_unless($grantee, 404);

        DB::table('consents')->insert([
            'patient_user_id' => $request->user()->id,
            'grantee_user_id' => $grantee->id,
            'scope' => $validated['scope'] ?? 'full_record',
            'granted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditService::log($request->user(), 'consent.granted', $grantee, []);

        return back()->with('success', 'Access granted to '.$grantee->masked_nin.'.');
    }

    public function consentRevoke(Request $request, int $id)
    {
        DB::table('consents')
            ->where('id', $id)
            ->where('patient_user_id', $request->user()->id)
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Access revoked.');
    }
}
