<?php

use App\Enums\Permission as P;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Clinical;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Facility;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patients;
use App\Http\Controllers\Portal;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoadmapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Each group is protected by the permission it needs. Permission values come
| from App\Enums\Permission, which is also the source for the role matrix.
*/

$can = fn (P ...$permissions) => 'permission:'.implode('|', array_map(fn (P $p) => $p->value, $permissions));

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('login', [Auth\LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [Auth\PasswordResetController::class, 'update'])->name('password.store');
});

Route::middleware('auth')->group(function () use ($can) {
    Route::post('logout', [Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('change-password', [Auth\ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('change-password', [Auth\ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('roadmap', RoadmapController::class)->name('roadmap');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::match(['get', 'post'], 'notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    /* System Administrator */
    Route::prefix('admin')->name('admin.')->group(function () use ($can) {
        Route::middleware($can(P::ManageFacilities))->group(function () {
            Route::resource('facilities', Admin\FacilityController::class)->except('destroy');
            Route::patch('facilities/{facility}/status', [Admin\FacilityController::class, 'toggleStatus'])->name('facilities.status');
        });
        Route::middleware($can(P::ManageFacilityAdministrators))->group(function () {
            Route::resource('facility-administrators', Admin\FacilityAdministratorController::class)
                ->parameters(['facility-administrators' => 'user'])->except(['show', 'destroy']);
            Route::patch('facility-administrators/{user}/status', [Admin\FacilityAdministratorController::class, 'toggleStatus'])->name('facility-administrators.status');
            Route::post('facility-administrators/{user}/reset-password', [Admin\FacilityAdministratorController::class, 'resetPassword'])->name('facility-administrators.reset-password');
        });
        Route::middleware($can(P::ManageSystemSettings))->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::resource('districts', Admin\DistrictController::class)->except(['show', 'destroy']);
        });
        Route::get('system-health', Admin\SystemHealthController::class)->middleware($can(P::ViewSystemHealth))->name('system-health');
        Route::resource('vaccines', Admin\VaccineController::class)->except(['show', 'destroy'])->middleware($can(P::ManageVaccineCatalogue));
    });

    Route::get('audit-log', [Admin\AuditLogController::class, 'index'])->middleware($can(P::ViewAuditLogs))->name('audit-log.index');

    Route::middleware($can(P::PublishCampaigns))->group(function () {
        Route::resource('campaigns', Admin\CampaignController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::post('campaigns/{campaign}/publish', [Admin\CampaignController::class, 'publish'])->name('campaigns.publish');
    });

    /* Facility Administrator */
    Route::prefix('facility')->name('facility.')->group(function () use ($can) {
        Route::middleware($can(P::ManageStaff))->group(function () {
            Route::resource('staff', Facility\StaffController::class)->parameters(['staff' => 'user'])->except(['show', 'destroy']);
            Route::patch('staff/{user}/status', [Facility\StaffController::class, 'toggleStatus'])->name('staff.status');
            Route::post('staff/{user}/reset-password', [Facility\StaffController::class, 'resetPassword'])->name('staff.reset-password');
        });
        Route::middleware($can(P::ManageWards))->group(function () {
            Route::resource('wards', Facility\WardController::class)->except('destroy');
            Route::post('wards/{ward}/beds', [Facility\WardController::class, 'addBeds'])->name('wards.beds.store');
            Route::patch('beds/{bed}', [Facility\WardController::class, 'updateBed'])->name('beds.update');
        });
        Route::get('bed-board', [Facility\WardController::class, 'board'])->middleware($can(P::ViewWardStatus))->name('bed-board');
        Route::middleware($can(P::ManageSchedules))->group(function () {
            Route::get('schedules', [Facility\ScheduleController::class, 'index'])->name('schedules.index');
            Route::post('schedules', [Facility\ScheduleController::class, 'store'])->name('schedules.store');
            Route::delete('schedules/{schedule}', [Facility\ScheduleController::class, 'destroy'])->name('schedules.destroy');
        });
        Route::middleware($can(P::ManageFacilityProfile))->group(function () {
            Route::get('profile', [Facility\FacilityProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Facility\FacilityProfileController::class, 'update'])->name('profile.update');
        });
        Route::get('reports', Facility\ReportController::class)->middleware($can(P::ViewFacilityReports))->name('reports');
    });

    /* Patients: registration and lookup */
    Route::middleware($can(P::ViewPatientDemographics))->group(function () {
        Route::get('patients', [Patients\PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/scan', [Patients\LookupController::class, 'scan'])->name('patients.scan');
        Route::post('patients/lookup', [Patients\LookupController::class, 'lookup'])->name('patients.lookup');
        Route::get('patients/mothers', [Patients\LookupController::class, 'mothers'])->name('patients.mothers');
    });
    Route::middleware($can(P::RegisterPatients))->group(function () {
        Route::get('patients/create', [Patients\PatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [Patients\PatientController::class, 'store'])->name('patients.store');
        Route::post('patients/{patient}/portal-account', [Patients\PatientController::class, 'createPortalAccount'])->name('patients.portal-account');
    });
    Route::get('patients/{patient}', [Patients\PatientController::class, 'show'])->name('patients.show');
    Route::get('patients/{patient}/card', [Patients\PatientController::class, 'card'])->name('patients.card');
    Route::middleware($can(P::EditPatientDemographics))->group(function () {
        Route::get('patients/{patient}/edit', [Patients\PatientController::class, 'edit'])->name('patients.edit');
        Route::put('patients/{patient}', [Patients\PatientController::class, 'update'])->name('patients.update');
    });

    /* Outpatient visits */
    Route::middleware($can(P::CheckInPatients))->group(function () {
        Route::post('patients/{patient}/visits', [Clinical\VisitController::class, 'store'])->name('visits.store');
        Route::patch('visits/{visit}/cancel', [Clinical\VisitController::class, 'cancel'])->name('visits.cancel');
    });
    Route::get('queue', [Clinical\VisitController::class, 'queue'])->middleware($can(P::CheckInPatients, P::RecordVitals, P::ConductConsultations))->name('visits.queue');
    Route::middleware($can(P::RecordVitals))->group(function () {
        Route::get('visits/{visit}/vitals', [Clinical\VitalController::class, 'create'])->name('vitals.create');
        Route::post('visits/{visit}/vitals', [Clinical\VitalController::class, 'store'])->name('vitals.store');
    });
    Route::middleware($can(P::ConductConsultations))->group(function () {
        Route::get('visits/{visit}/consultation', [Clinical\ConsultationController::class, 'create'])->name('consultations.create');
        Route::post('visits/{visit}/consultation', [Clinical\ConsultationController::class, 'store'])->name('consultations.store');
    });
    Route::get('visits/{visit}/report', [Clinical\VisitController::class, 'report'])->name('visits.report');

    /* Pharmacy */
    Route::middleware($can(P::DispenseMedication))->group(function () {
        Route::get('pharmacy', [Clinical\PharmacyController::class, 'index'])->name('pharmacy.index');
        Route::get('pharmacy/{prescription}', [Clinical\PharmacyController::class, 'show'])->name('pharmacy.show');
        Route::post('pharmacy/{prescription}/dispense', [Clinical\PharmacyController::class, 'dispense'])->name('pharmacy.dispense');
        Route::post('pharmacy/{prescription}/cancel', [Clinical\PharmacyController::class, 'cancel'])->name('pharmacy.cancel');
    });
    Route::middleware($can(P::ManageMedicineStock))->group(function () {
        Route::resource('medicines', Clinical\MedicineController::class)->except(['show', 'destroy']);
    });

    /* Inpatient care */
    Route::get('admissions', [Clinical\AdmissionController::class, 'index'])->middleware($can(P::ViewWardStatus))->name('admissions.index');
    Route::get('admissions/{admission}', [Clinical\AdmissionController::class, 'show'])->name('admissions.show');
    Route::post('admissions/{admission}/bed', [Clinical\AdmissionController::class, 'allocateBed'])->middleware($can(P::AllocateBeds))->name('admissions.allocate-bed');
    Route::post('admissions/{admission}/vitals', [Clinical\AdmissionController::class, 'storeVitals'])->middleware($can(P::RecordVitals))->name('admissions.vitals');
    Route::post('admissions/{admission}/notes', [Clinical\AdmissionController::class, 'storeNote'])->middleware($can(P::WriteProgressNotes))->name('admissions.notes');
    Route::post('admissions/{admission}/medication', [Clinical\AdmissionController::class, 'storeMedication'])->middleware($can(P::RecordMedicationAdministration))->name('admissions.medication');
    Route::post('admissions/{admission}/prescriptions', [Clinical\AdmissionController::class, 'storePrescription'])->middleware($can(P::PrescribeMedication))->name('admissions.prescriptions');
    Route::get('admissions/{admission}/discharge', [Clinical\AdmissionController::class, 'dischargeForm'])->middleware($can(P::DischargePatients))->name('admissions.discharge');
    Route::post('admissions/{admission}/discharge', [Clinical\AdmissionController::class, 'discharge'])->middleware($can(P::DischargePatients))->name('admissions.discharge.store');
    Route::get('admissions/{admission}/report', [Clinical\AdmissionController::class, 'report'])->name('admissions.report');

    /* Vaccinations and reminders */
    Route::middleware($can(P::RecordVaccinations))->group(function () {
        Route::get('patients/{patient}/vaccinations/create', [Clinical\VaccinationController::class, 'create'])->name('vaccinations.create');
        Route::post('patients/{patient}/vaccinations', [Clinical\VaccinationController::class, 'store'])->name('vaccinations.store');
    });
    Route::middleware($can(P::ManageReminders))->group(function () {
        Route::get('patients/{patient}/reminders/create', [Clinical\ReminderController::class, 'create'])->name('reminders.create');
        Route::post('patients/{patient}/reminders', [Clinical\ReminderController::class, 'store'])->name('reminders.store');
        Route::patch('reminders/{reminder}/stop', [Clinical\ReminderController::class, 'stop'])->name('reminders.stop');
    });

    /* Appointments, staff side */
    Route::middleware($can(P::ApproveAppointments))->group(function () {
        Route::get('appointments', [Clinical\AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments/{appointment}/decision', [Clinical\AppointmentController::class, 'decide'])->name('appointments.decide');
        Route::post('appointments/{appointment}/complete', [Clinical\AppointmentController::class, 'complete'])->name('appointments.complete');
    });

    /* Patient portal */
    Route::prefix('my')->name('portal.')->middleware($can(P::UsePatientPortal))->group(function () {
        Route::get('records', [Portal\RecordController::class, 'index'])->name('records');
        Route::get('card', [Portal\RecordController::class, 'card'])->name('card');
        Route::get('appointments', [Portal\AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/book', [Portal\AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [Portal\AppointmentController::class, 'store'])->name('appointments.store');
        Route::patch('appointments/{appointment}/cancel', [Portal\AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::post('appointments/{appointment}/review', [Portal\AppointmentController::class, 'review'])->name('appointments.review');
    });
});
