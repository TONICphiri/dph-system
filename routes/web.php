<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserController;
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

Route::get('/ward/{patient}', [PatientController::class, 'ward'])
    ->name('ward')
    ->middleware(['auth', 'verified']);

Route::post('/ward/medication-admin', [PatientController::class, 'administerMedication'])
    ->name('ward.medication-admin')
    ->middleware(['auth']);

Route::post('/ward/progress-note', [PatientController::class, 'saveProgressNote'])
    ->name('ward.progress-note')
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

Route::post('/sync/retry/{id}', [PatientController::class, 'syncRetry'])
    ->name('sync.retry')
    ->middleware(['auth']);

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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

    // Audit Logs Routes
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

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
    Route::resource('patients', PatientController::class);
    Route::get('/api/patients/search-by-national-id', [PatientController::class, 'searchByNationalId'])->name('patients.search.national-id');
    Route::get('/api/patients/search-by-dhp-id', [PatientController::class, 'searchByDhpId'])->name('patients.search.dhp-id');
    Route::get('/patients/{patient}/qr-code', [PatientController::class, 'showQrCode'])->name('patients.qr-code');
    Route::get('/api/patients/{patient}/qr-code', [PatientController::class, 'getQrCode'])->name('patients.qr-code.api');

    /** Lab Orders Routes */
    Route::get('/lab/orders', [LabOrderController::class, 'index'])->name('lab.orders.index');
    Route::get('/lab/orders/create/{patient}', [LabOrderController::class, 'create'])->name('lab.orders.create');
    Route::post('/lab/orders', [LabOrderController::class, 'store'])->name('lab.orders.store');
    Route::get('/lab/orders/{labOrder}', [LabOrderController::class, 'show'])->name('lab.orders.show');
    Route::post('/lab/orders/{labOrder}/results', [LabOrderController::class, 'updateResults'])->name('lab.orders.results');
    Route::get('/lab/orders/patient/{patient}', [LabOrderController::class, 'patientOrders'])->name('lab.orders.patient');
});

require __DIR__.'/auth.php';
