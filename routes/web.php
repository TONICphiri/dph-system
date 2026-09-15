<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AdminLandingSlideController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FacilitySettingsController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

// The /test-* and /test-error-page-* routes below are for local/staging use only.
// Wrapped so they can never be reached in production.
if (! app()->environment('production')) {
    Route::get('/test-server-error', function () {
        throw new \Exception('Test 500 Error for DHP System');
    });

    Route::get('/test-error-page-403', fn () => view('errors.403'));
    Route::get('/test-error-page-404', fn () => view('errors.404'));
    Route::get('/test-error-page-419', fn () => view('errors.419'));
    Route::get('/test-error-page-429', fn () => view('errors.429'));
    Route::get('/test-error-page-500', fn () => view('errors.500'));
    Route::get('/test-error-page-503', fn () => view('errors.503'));
}

Route::get('/triage/{patient}', [PatientController::class, 'triage'])
    ->name('triage')
    ->middleware(['auth', 'verified']);

Route::post('/triage/save', [PatientController::class, 'saveTriage'])
    ->name('triage.save')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/consultation/{patient}', [PatientController::class, 'consultation'])
    ->name('consultation')
    ->middleware(['auth', 'verified']);

Route::post('/consultation/save', [PatientController::class, 'saveConsultation'])
    ->name('consultation.save')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/pharmacy/{patient}', [PatientController::class, 'pharmacy'])
    ->name('pharmacy')
    ->middleware(['auth', 'verified']);

Route::post('/pharmacy/dispense', [PatientController::class, 'dispenseMedication'])
    ->name('pharmacy.dispense')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/admission/{patient}', [PatientController::class, 'admission'])
    ->name('admission')
    ->middleware(['auth', 'verified']);

Route::post('/admission/create', [PatientController::class, 'createAdmission'])
    ->name('admission.create')
    ->middleware(['auth', 'throttle:30,1']);

Route::get('/ward/round/{patient}', [PatientController::class, 'showWardRoundForm'])
    ->name('ward.round.form')
    ->middleware(['auth', 'verified']);

Route::post('/ward/round', [PatientController::class, 'wardRound'])
    ->name('ward.round')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/ward/{patient}', [PatientController::class, 'ward'])
    ->name('ward')
    ->middleware(['auth', 'verified']);

Route::post('/ward/medication-admin', [PatientController::class, 'administerMedication'])
    ->name('ward.medication-admin')
    ->middleware(['auth', 'throttle:60,1']);

Route::post('/ward/progress-note', [PatientController::class, 'saveProgressNote'])
    ->name('ward.progress-note')
    ->middleware(['auth', 'throttle:60,1']);

Route::get('/discharge/{patient}', [PatientController::class, 'showDischargeForm'])
    ->name('discharge')
    ->middleware(['auth', 'verified']);

Route::post('/discharge/{patient}', [PatientController::class, 'dischargePatient'])
    ->name('discharge.store')
    ->middleware(['auth', 'throttle:30,1']);

Route::get('/sync/status', [PatientController::class, 'syncStatus'])
    ->name('sync.status')
    ->middleware(['auth']);

Route::post('/sync/upload', [PatientController::class, 'syncUpload'])
    ->name('sync.upload')
    ->middleware(['auth']);

Route::post('/sync/retry/{id}', [PatientController::class, 'syncRetry'])
    ->name('sync.retry')
    ->middleware(['auth']);

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return view('landing');
})->name('landing');

// Shown by the service worker when the server cannot be reached at all.
Route::get('/offline', fn () => view('offline'))->name('offline');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'must.change_password'])->name('dashboard');

// Public credential verification (minimal data only, FR-E1). No login required.
Route::get('/verify/scan', [VerifyController::class, 'scan'])->name('verify.scan');
Route::post('/verify/scan', [VerifyController::class, 'check'])->name('verify.check')->middleware('throttle:60,1');

Route::middleware('auth')->group(function () {
    // First-login activation (FR-A5): allowed even with must_change_password flag.
    Route::get('/activate/password', [ActivationController::class, 'showPassword'])->name('activate.password');
    Route::post('/activate/password', [ActivationController::class, 'storePassword'])->name('activate.password.store');
    Route::get('/activate/{user}', [ActivationController::class, 'showSigned'])->name('activate.signed');

    // Two-factor setup + challenge (required for patient medical details).
    Route::get('/settings/2fa', [TwoFactorController::class, 'settings'])->name('settings.2fa');
    Route::post('/settings/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('settings.2fa.confirm');
    Route::post('/settings/2fa/disable', [TwoFactorController::class, 'disable'])->name('settings.2fa.disable');
    Route::get('/settings/2fa/recovery', [TwoFactorController::class, 'recovery'])->name('settings.2fa.recovery');
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});

Route::middleware(['auth', 'must.change_password'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory Management Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::post('/inventory/{item}/restock', [InventoryController::class, 'restock'])->name('inventory.restock');

    // Admin Management Routes
    Route::resource('facilities', FacilityController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // System Settings Routes: facility profile (Facility Admin, own
    // facility) and national passport configuration (National Admin).
    Route::get('/settings/facility', [FacilitySettingsController::class, 'edit'])->name('settings.facility.edit');
    Route::put('/settings/facility', [FacilitySettingsController::class, 'update'])->name('settings.facility.update');
    Route::get('/settings/global', [GlobalSettingsController::class, 'edit'])->name('settings.global.edit');
    Route::put('/settings/global', [GlobalSettingsController::class, 'update'])->name('settings.global.update');

    // Audit Logs Routes
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // National administration home (National Admins only, enforced in controller)
    Route::get('/admin/dashboard', AdminDashboardController::class)->name('admin.dashboard');

    // Reports Routes
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/census', [ReportsController::class, 'census'])->name('reports.census');
    Route::get('/reports/opd-visits', [ReportsController::class, 'opdVisits'])->name('reports.opd-visits');
    Route::get('/reports/admissions', [ReportsController::class, 'admissions'])->name('reports.admissions');
    Route::get('/reports/dispensed-meds', [ReportsController::class, 'dispensedMeds'])->name('reports.dispensed-meds');
    Route::get('/reports/inventory', [ReportsController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/medical-clearance', [ReportsController::class, 'medicalClearance'])->name('reports.medical-clearance');
    Route::post('/reports/medical-clearance/pdf', [ReportsController::class, 'medicalClearancePdf'])->name('reports.medical-clearance.pdf');

    // Patient Management Routes
    Route::resource('patients', PatientController::class)->middleware('throttle:120,1');
    Route::get('/api/patients/search-by-national-id', [PatientController::class, 'searchByNationalId'])->name('patients.search.national-id')->middleware('throttle:120,1');
    Route::get('/api/patients/search-by-dhp-id', [PatientController::class, 'searchByDhpId'])->name('patients.search.dhp-id')->middleware('throttle:120,1');
    Route::get('/patients/{patient}/qr-code', [PatientController::class, 'showQrCode'])->name('patients.qr-code');
    Route::get('/api/patients/{patient}/qr-code', [PatientController::class, 'getQrCode'])->name('patients.qr-code.api');

    /**
     * Lab Orders Routes
     *
     * NOTE: '/lab/orders/patient/{patient}' and '/lab/orders/create/{patient}' MUST be
     * registered before '/lab/orders/{labOrder}'. Laravel matches routes top to bottom,
     * and '{labOrder}' is a wildcard that would otherwise swallow any single-segment
     * path (e.g. a request to /lab/orders/patient/5 would incorrectly hit show()
     * instead of patientOrders(), trying to bind "patient" as a LabOrder and failing).
     */
    Route::get('/lab/orders', [LabOrderController::class, 'index'])->name('lab.orders.index');
    Route::get('/lab/orders/create/{patient}', [LabOrderController::class, 'create'])->name('lab.orders.create');
    Route::post('/lab/orders', [LabOrderController::class, 'store'])->name('lab.orders.store');
    Route::get('/lab/orders/patient/{patient}', [LabOrderController::class, 'patientOrders'])->name('lab.orders.patient');
    Route::get('/lab/orders/{labOrder}', [LabOrderController::class, 'show'])->name('lab.orders.show');
    Route::post('/lab/orders/{labOrder}/results', [LabOrderController::class, 'updateResults'])->name('lab.orders.results');

    // ---- User Catalogue: facility enrollment (FR-A1..A3, FR-D1/D3, §6.3) ----
    Route::get('/enroll/create', [EnrollmentController::class, 'create'])->name('enroll.create');
    Route::post('/enroll', [EnrollmentController::class, 'store'])->name('enroll.store');
    Route::get('/enroll/pending', [EnrollmentController::class, 'pending'])->name('enroll.pending');
    Route::post('/enroll/check-nin', [EnrollmentController::class, 'checkNin'])->name('enroll.check-nin');
    Route::get('/facility/approvals', [EnrollmentController::class, 'pending'])->name('facility.approvals');
    Route::post('/facility/approvals/{user}/approve', [EnrollmentController::class, 'approve'])->name('facility.approvals.approve');
    Route::post('/facility/approvals/{user}/reject', [EnrollmentController::class, 'reject'])->name('facility.approvals.reject');
    Route::get('/facility/identity-services', [EnrollmentController::class, 'identityServices'])->name('facility.identity-services');
    Route::post('/facility/identity-services/reset', [EnrollmentController::class, 'identityReset'])->name('facility.identity-services.reset');
    Route::post('/facility/users/{user}/link-file', [EnrollmentController::class, 'linkFile'])->name('facility.users.link-file');

    // ---- Patient passport: QR credential, appointments, consent (FR-B2..B5, FR-C1) ----
    Route::get('/patient/credential', [PassportController::class, 'credential'])->name('patient.credential')->middleware('twofactor');
    Route::post('/patient/credential/issue', [PassportController::class, 'issueCredential'])->name('patient.credential.issue');
    Route::post('/patient/credential/{id}/revoke', [PassportController::class, 'revokeCredential'])->name('patient.credential.revoke');
    // Own medical details: linked file only + enforced 2FA (FR-B1).
    Route::get('/patient/records', [PassportController::class, 'records'])->name('patient.records')->middleware('twofactor');
    Route::get('/patient/appointments', [PassportController::class, 'appointments'])->name('patient.appointments');
    Route::post('/patient/appointments', [PassportController::class, 'storeAppointment'])->name('patient.appointments.store');
    Route::get('/patient/consents', [PassportController::class, 'consentIndex'])->name('patient.consents');
    Route::post('/patient/consents', [PassportController::class, 'consentStore'])->name('patient.consents.store');
    Route::post('/patient/consents/{id}/revoke', [PassportController::class, 'consentRevoke'])->name('patient.consents.revoke');

    // ---- Landing slideshow (main/national admin only, NOT facility admin) ----
    Route::get('/admin/landing-slides', [AdminLandingSlideController::class, 'index'])->name('admin.landing-slides.index');
    Route::post('/admin/landing-slides', [AdminLandingSlideController::class, 'store'])->name('admin.landing-slides.store');
    Route::put('/admin/landing-slides/{slide}', [AdminLandingSlideController::class, 'update'])->name('admin.landing-slides.update');
    Route::delete('/admin/landing-slides/{slide}', [AdminLandingSlideController::class, 'destroy'])->name('admin.landing-slides.destroy');
    Route::post('/admin/landing-slides/interval', [AdminLandingSlideController::class, 'updateInterval'])->name('admin.landing-slides.interval');

    // ---- Verifier history (FR-E2) ----    Route::get('/verify/history', [VerifyController::class, 'history'])->name('verify.history');
});

require __DIR__.'/auth.php';