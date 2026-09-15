<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\EnrollmentService;
use App\Services\NinLookupService;
use Illuminate\Http\Request;

/**
 * Facility enrollment + approvals + identity services desk
 * per system-description2.md FR-A1..A3, FR-D1, FR-D3, §5.4, §6.4.
 */
class EnrollmentController extends Controller
{
    public function create()
    {
        return view('enroll.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nin' => ['required', 'string', 'min:6', 'max:30'],
            'full_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:M,F,Other,male,female,other'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_document_ref' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:patient,practitioner,facility_admin,verifier'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'physical_verification' => ['accepted'],
            'staff_pin_confirm' => ['required', 'string'],
        ]);

        // Staff re-enters their own password as the declaration PIN.
        if (! \Hash::check($validated['staff_pin_confirm'], $request->user()->password)) {
            return back()->withErrors(['staff_pin_confirm' => 'Staff confirmation failed.'])->withInput();
        }

        unset($validated['staff_pin_confirm']);
        $physical = (bool) ($validated['physical_verification'] ?? false);
        unset($validated['physical_verification']);

        if (! $physical) {
            return back()->withErrors(['physical_verification' => 'Physical ID inspection must be confirmed.'])->withInput();
        }

        try {
            $user = EnrollmentService::enroll($validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['nin' => $e->getMessage()])->withInput();
        }

        return redirect()->route('enroll.pending')->with('success', 'Enrollment submitted. Account PENDING approval ('.$user->masked_nin.').');
    }

    public function pending(Request $request)
    {
        $query = \App\Models\User::where('status', 'pending')->latest();

        if ($request->user()->isFacilityScoped()) {
            $query->where('facility_id', $request->user()->facility_id);
        }

        return view('enroll.pending', ['pending' => $query->paginate(15)]);
    }

    /** Live NIN uniqueness check for the wizard step 1 (FR-A3). */
    public function checkNin(Request $request)
    {
        $request->validate(['nin' => ['required', 'string']]);

        $result = EnrollmentService::checkNin($request->input('nin'));

        if (! $result['ok']) {
            return response()->json([
                'available' => false,
                'message' => $result['message'],
                'masked' => $result['existing']?->masked_nin,
            ]);
        }

        return response()->json(['available' => true, 'masked' => NinLookupService::mask($request->input('nin'))]);
    }

    public function approve(\App\Models\User $user, Request $request)
    {
        $this->authorizeApproval($request->user(), $user);

        EnrollmentService::approve($user, $request->user());

        return back()->with('success', 'Enrollment approved. Activation credentials may now be issued to '.$user->masked_nin.'.');
    }

    public function reject(\App\Models\User $user, Request $request)
    {
        $this->authorizeApproval($request->user(), $user);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        EnrollmentService::reject($user, $request->user(), (string) $request->input('reason', ''));

        return back()->with('success', 'Enrollment rejected and logged.');
    }

    /** Identity services desk: verified person present → staff triggers reset/recovery (FR-D3, FR-A6). */
    public function identityServices()
    {
        return view('facility.identity-services');
    }

    public function identityReset(Request $request)
    {
        $validated = $request->validate([
            'nin' => ['required', 'string'],
            'staff_pin_confirm' => ['required', 'string'],
        ]);

        if (! \Hash::check($validated['staff_pin_confirm'], $request->user()->password)) {
            return back()->withErrors(['staff_pin_confirm' => 'Staff confirmation failed.']);
        }

        $subject = NinLookupService::findByNin($validated['nin']);

        if (! $subject) {
            // Uniform message: do not reveal whether the NIN exists.
            return back()->with('success', 'If the identity exists, a recovery action was logged.');
        }

        $subject->forceFill([
            'must_change_password' => true,
            'locked_until' => null,
            'failed_login_attempts' => 0,
            // Lost authenticator: patient re-enrolls 2FA at next sign-in.
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->saveQuietly();

        AuditService::log($request->user(), 'identity.recovery', $subject, [
            'facility_id' => $request->user()->facility_id,
        ]);

        return back()->with('success', 'Identity verified. Recovery logged for '.$subject->masked_nin.'. Issue the one-time setup link.');
    }

    /** Link a catalogue account to its clinical file (explicit staff action, logged). */
    public function linkFile(\App\Models\User $user, Request $request)
    {
        $this->authorizeLink($request->user(), $user);

        $validated = $request->validate([
            'dhp_id' => ['required', 'string', 'max:64'],
        ]);

        $patient = \App\Models\Patient::where('dhp_id', $validated['dhp_id'])->first();
        abort_unless($patient, 404, 'No clinical file with that DHP ID.');

        $user->forceFill(['patient_id' => $patient->id])->saveQuietly();

        AuditService::log($request->user(), 'catalogue.linked', $user, [
            'facility_id' => $user->facility_id,
        ]);

        return back()->with('success', 'Account '.$user->masked_nin.' linked to file '.$patient->dhp_id.'.');
    }

    protected function authorizeApproval($actor, $subject): void
    {
        $this->authorizeLink($actor, $subject);
    }

    protected function authorizeLink($actor, $subject): void
    {
        abort_unless(
            $actor->hasAnyRole(['super_admin', 'system_admin', 'admin', 'national_admin', 'facility_admin']),
            403
        );

        if ($actor->isFacilityScoped() && $subject->facility_id !== $actor->facility_id) {
            abort(403);
        }
    }
}
