<?php

use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/test-server-error', function () {
    throw new \Exception("Test 500 Error for DHP System");
});


Route::get('/test-error-page-403', function () {
    return view('errors.403');
});


Route::get('/test-error-page-404', function () {
    return view('errors.404');
});

Route::get('/test-error-page-419', function () {
    return view('errors.419');
});

Route::get('/test-error-page-429', function () {
    return view('errors.429');
});

Route::get('/test-error-page-500', function () {
    return view('errors.500');
});

Route::get('/test-error-page-503', function () {
    return view('errors.503');
});

Route::get('/triage/{patient}', [PatientController::class, 'triage'])
    ->name('triage')
    ->middleware(['auth', 'verified']);

Route::post('/triage/save', [PatientController::class, 'saveTriage'])
    ->name('triage.save')
    ->middleware(['auth']);

Route::get('/consultation/{patient}', [PatientController::class, 'consultation'])
    ->name('consultation')
    ->middleware(['auth', 'verified']);

Route::post('/consultation/save', [PatientController::class, 'saveConsultation'])
    ->name('consultation.save')
    ->middleware(['auth']);

Route::get('/pharmacy/{patient}', [PatientController::class, 'pharmacy'])
    ->name('pharmacy')
    ->middleware(['auth', 'verified']);

Route::post('/pharmacy/dispense', [PatientController::class, 'dispenseMedication'])
    ->name('pharmacy.dispense')
    ->middleware(['auth']);

Route::get('/admission/{patient}', [PatientController::class, 'admission'])
    ->name('admission')
    ->middleware(['auth', 'verified']);

Route::post('/admission/create', [PatientController::class, 'createAdmission'])
    ->name('admission.create')
    ->middleware(['auth']);

Route::post('/ward/round', [PatientController::class, 'wardRound'])
    ->name('ward.round')
    ->middleware(['auth']);

Route::post('/discharge/{patient}', [PatientController::class, 'dischargePatient'])
    ->name('discharge')
    ->middleware(['auth']);

Route::get('/sync/status', [PatientController::class, 'syncStatus'])
    ->name('sync.status')
    ->middleware(['auth']);

Route::post('/sync/upload', [PatientController::class, 'syncUpload'])
    ->name('sync.upload')
    ->middleware(['auth']);

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Patient Management Routes
    Route::resource('patients', PatientController::class);
    Route::get('/patients/{patient}/qr-code', [PatientController::class, 'showQrCode'])->name('patients.qr-code');
    Route::get('/api/patients/{patient}/qr-code', [PatientController::class, 'getQrCode'])->name('patients.qr-code.api');
});

require __DIR__.'/auth.php';
