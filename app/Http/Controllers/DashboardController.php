<?php

namespace App\Http\Controllers;

use App\Enums\AdmissionStatus;
use App\Enums\AppointmentStatus;
use App\Enums\BedStatus;
use App\Enums\FacilityStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\ReminderStatus;
use App\Enums\RoleName;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Visit;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shows each user the dashboard for their role. Every figure is read from
 * the database and limited to the user's own facility.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, SystemHealthService $health): View
    {
        $user = $request->user();

        return match ($user->role()) {
            RoleName::SystemAdmin => $this->systemAdmin($health),
            RoleName::FacilityAdmin => $this->facilityAdmin($user),
            RoleName::Clerk => $this->clerk($user),
            RoleName::Nurse => $this->nurse($user),
            RoleName::Doctor => $this->doctor($user),
            RoleName::Pharmacist => $this->pharmacist($user),
            RoleName::Patient => $this->patient($user),
            default => abort(403, 'Your account does not have a role. Please contact your administrator.'),
        };
    }

    private function systemAdmin(SystemHealthService $health): View
    {
        return view('dashboards.system-admin', [
            'stats' => [
                'facilities' => Facility::query()->where('status', FacilityStatus::Active)->count(),
                'facilityAdmins' => User::query()->withRole(RoleName::FacilityAdmin)->count(),
                'staff' => User::query()->whereNotNull('facility_id')->count(),
                'patients' => Patient::query()->count(),
            ],
            'facilities' => Facility::query()->with('district')->withCount(['users', 'patients'])->latest()->limit(6)->get(),
            'checks' => $health->checks(),
            'activity' => AuditLog::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }

    private function facilityAdmin(User $user): View
    {
        $facilityId = $user->facility_id;
        $beds = Bed::query()->whereHas('ward', fn ($query) => $query->where('facility_id', $facilityId));

        return view('dashboards.facility-admin', [
            'stats' => [
                'staff' => User::query()->where('facility_id', $facilityId)->active()->count(),
                'visitsToday' => Visit::query()->where('facility_id', $facilityId)->whereDate('checked_in_at', today())->count(),
                'inpatients' => Admission::query()->where('facility_id', $facilityId)->current()->count(),
                'bedsAvailable' => (clone $beds)->where('status', BedStatus::Available)->count(),
                'bedsTotal' => (clone $beds)->count(),
                'pendingAppointments' => Appointment::query()->where('facility_id', $facilityId)->where('status', AppointmentStatus::Pending)->count(),
            ],
            'awaitingBed' => Admission::query()->with('patient')->where('facility_id', $facilityId)
                ->where('status', AdmissionStatus::AwaitingBed)->oldest('admitted_at')->limit(5)->get(),
            'appointments' => Appointment::query()->with(['patient', 'doctor'])->where('facility_id', $facilityId)
                ->where('status', AppointmentStatus::Pending)->orderBy('appointment_date')->limit(5)->get(),
            'lowStock' => Medicine::query()->where('facility_id', $facilityId)->lowStock()->limit(5)->get(),
            'wards' => $user->facility->wards()->withCount([
                'beds',
                'beds as occupied_count' => fn ($query) => $query->where('status', BedStatus::Occupied),
            ])->get(),
        ]);
    }

    private function clerk(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.clerk', [
            'stats' => [
                'registeredToday' => Patient::query()->where('registered_facility_id', $facilityId)->whereDate('created_at', today())->count(),
                'checkedInToday' => Visit::query()->where('facility_id', $facilityId)->whereDate('checked_in_at', today())->count(),
                'waiting' => Visit::query()->where('facility_id', $facilityId)
                    ->whereIn('status', [VisitStatus::WaitingForVitals, VisitStatus::WaitingForDoctor])->count(),
            ],
            'recentPatients' => Patient::query()->where('registered_facility_id', $facilityId)->latest()->limit(8)->get(),
        ]);
    }

    private function nurse(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.nurse', [
            'stats' => [
                'waitingForVitals' => Visit::query()->where('facility_id', $facilityId)->where('status', VisitStatus::WaitingForVitals)->count(),
                'inpatients' => Admission::query()->where('facility_id', $facilityId)->where('status', AdmissionStatus::Admitted)->count(),
                'awaitingBed' => Admission::query()->where('facility_id', $facilityId)->where('status', AdmissionStatus::AwaitingBed)->count(),
            ],
            'vitalsQueue' => Visit::query()->with('patient')->where('facility_id', $facilityId)
                ->where('status', VisitStatus::WaitingForVitals)->oldest('checked_in_at')->limit(8)->get(),
            'inpatients' => Admission::query()->with(['patient', 'ward', 'bed'])->where('facility_id', $facilityId)
                ->current()->oldest('admitted_at')->limit(8)->get(),
        ]);
    }

    private function doctor(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.doctor', [
            'stats' => [
                'waitingForDoctor' => Visit::query()->where('facility_id', $facilityId)->where('status', VisitStatus::WaitingForDoctor)->count(),
                'seenToday' => Visit::query()->where('doctor_id', $user->id)->whereDate('consulted_at', today())->count(),
                'myInpatients' => Admission::query()->where('admitted_by', $user->id)->current()->count(),
                'myAppointmentsToday' => Appointment::query()->where('doctor_id', $user->id)->whereDate('appointment_date', today())
                    ->where('status', AppointmentStatus::Approved)->count(),
            ],
            'consultationQueue' => Visit::query()->with(['patient', 'vitals'])->where('facility_id', $facilityId)
                ->where('status', VisitStatus::WaitingForDoctor)->oldest('checked_in_at')->limit(8)->get(),
            'inpatients' => Admission::query()->with(['patient', 'ward', 'bed'])->where('facility_id', $facilityId)
                ->current()->oldest('admitted_at')->limit(6)->get(),
            'appointments' => Appointment::query()->with('patient')->where('doctor_id', $user->id)
                ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Approved])
                ->whereDate('appointment_date', '>=', today())->orderBy('appointment_date')->limit(6)->get(),
        ]);
    }

    private function pharmacist(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.pharmacist', [
            'stats' => [
                'pending' => Prescription::query()->where('facility_id', $facilityId)->where('status', PrescriptionStatus::Pending)->count(),
                'dispensedToday' => Prescription::query()->where('facility_id', $facilityId)->where('status', PrescriptionStatus::Dispensed)
                    ->whereDate('dispensed_at', today())->count(),
                'lowStock' => Medicine::query()->where('facility_id', $facilityId)->lowStock()->count(),
            ],
            'queue' => Prescription::query()->with(['patient', 'items', 'prescriber'])->where('facility_id', $facilityId)
                ->where('status', PrescriptionStatus::Pending)->oldest()->limit(8)->get(),
            'lowStock' => Medicine::query()->where('facility_id', $facilityId)->lowStock()->limit(6)->get(),
        ]);
    }

    private function patient(User $user): View
    {
        $patient = $user->patient;
        abort_if(! $patient, 403, 'This account is not linked to a health passport. Please contact the registration desk.');

        $patient->load(['children', 'registeredFacility']);
        $familyIds = $patient->children->pluck('id')->push($patient->id);

        return view('dashboards.patient', [
            'patient' => $patient,
            'upcomingAppointments' => Appointment::query()->with(['facility', 'doctor'])->where('patient_id', $patient->id)
                ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Approved])
                ->whereDate('appointment_date', '>=', today())->orderBy('appointment_date')->limit(4)->get(),
            'reminders' => Reminder::query()->with('patient')->whereIn('patient_id', $familyIds)
                ->where('status', ReminderStatus::Active)->orderBy('due_on')->limit(5)->get(),
            'recentVisits' => Visit::query()->with('facility')->where('patient_id', $patient->id)->latest('checked_in_at')->limit(5)->get(),
        ]);
    }
}
