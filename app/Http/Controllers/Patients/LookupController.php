<?php

namespace App\Http\Controllers\Patients;

use App\Enums\Sex;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\QrCodeService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Finding a patient by scanning the QR code on the passport card or by
 * typing the passport number or National ID.
 */
class LookupController extends Controller
{
    public function scan(): View
    {
        return view('patients.scan');
    }

    public function lookup(Request $request, QrCodeService $qr): RedirectResponse
    {
        $value = trim((string) $request->validate(['code' => ['required', 'string', 'max:120']])['code']);
        $token = $qr->tokenFromScan($value);

        $patient = $token
            ? Patient::query()->where('qr_token', $token)->first()
            : Patient::query()
                ->where('passport_number', strtoupper($value))
                ->orWhere('national_id', strtoupper($value))
                ->first();

        if (! $patient) {
            return back()->withInput()->with('error', 'No patient was found for that code. Check the number and try again.');
        }

        return redirect()->route('patients.show', $patient);
    }

    /**
     * Search used by the registration form to link a child to the mother.
     */
    public function mothers(Request $request, SettingService $settings): JsonResponse
    {
        $term = (string) $request->input('search');

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $adultBefore = today()->subYears($settings->childSeparationAge());

        $mothers = Patient::query()
            ->where('sex', Sex::Female)
            ->whereDate('date_of_birth', '<=', $adultBefore)
            ->search($term)
            ->limit(8)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'name' => $patient->full_name,
                'passport_number' => $patient->passport_number,
                'national_id' => $patient->national_id,
                'phone' => $patient->phone,
                'district_id' => $patient->district_id,
                'village' => $patient->village,
                'traditional_authority' => $patient->traditional_authority,
            ]);

        return response()->json($mothers);
    }
}
