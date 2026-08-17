<?php

use App\Http\Controllers\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    // Patient Search API
    Route::post('/patient/search-by-national-id', [PatientController::class, 'searchByNationalId']);
    Route::post('/patient/search-by-dhp-id', [PatientController::class, 'searchByDhpId']);
    
    // QR Code endpoint
    Route::get('/patient/{patient}/qr-code', function ($patientId) {
        $patient = \App\Models\Patient::findOrFail($patientId);
        \Gate::authorize('view_patient');
        
        return response()->json([
            'dhp_id' => $patient->dhp_id,
            'qr_code_data' => json_encode([
                'dhp_id' => $patient->dhp_id,
                'patient_id' => $patient->id,
                'generated_at' => now()->toIso8601String(),
            ]),
        ]);
    });
});
