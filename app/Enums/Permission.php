<?php

namespace App\Enums;

/**
 * Every permission in the system. Roles are granted a subset of these in
 * RoleName::permissions(), and the seeder stores them in the database.
 */
enum Permission: string
{
    use HasOptions;

    // Platform administration
    case ManageFacilities = 'facilities.manage';
    case ManageFacilityAdministrators = 'facility-administrators.manage';
    case ManageSystemSettings = 'system-settings.manage';
    case ViewSystemHealth = 'system-health.view';
    case ManageVaccineCatalogue = 'vaccine-catalogue.manage';
    case ViewAuditLogs = 'audit-logs.view';
    case PublishCampaigns = 'campaigns.publish';

    // Facility administration
    case ManageStaff = 'staff.manage';
    case ManageWards = 'wards.manage';
    case ManageSchedules = 'schedules.manage';
    case ManageFacilityProfile = 'facility-profile.manage';
    case ViewFacilityReports = 'facility-reports.view';
    case ApproveAppointments = 'appointments.approve';

    // Patient registration
    case ViewPatientDemographics = 'patients.view-demographics';
    case RegisterPatients = 'patients.register';
    case EditPatientDemographics = 'patients.edit-demographics';
    case CheckInPatients = 'visits.check-in';

    // Clinical care
    case RecordVitals = 'vitals.record';
    case ViewVitals = 'vitals.view';
    case ViewBasicHistory = 'history.view-basic';
    case ViewFullMedicalRecord = 'history.view-full';
    case ConductConsultations = 'consultations.conduct';
    case PrescribeMedication = 'prescriptions.create';
    case AdmitPatients = 'admissions.admit';
    case AllocateBeds = 'admissions.allocate-bed';
    case ViewWardStatus = 'wards.view-status';
    case WriteProgressNotes = 'admissions.write-notes';
    case RecordMedicationAdministration = 'admissions.record-medication';
    case DischargePatients = 'admissions.discharge';
    case RecordVaccinations = 'vaccinations.record';
    case ViewVaccinations = 'vaccinations.view';
    case ManageReminders = 'reminders.manage';

    // Pharmacy
    case ViewPrescriptions = 'prescriptions.view';
    case DispenseMedication = 'prescriptions.dispense';
    case ManageMedicineStock = 'medicines.manage';

    // Patient portal
    case UsePatientPortal = 'portal.use';

    public function label(): string
    {
        return match ($this) {
            self::ManageFacilities => 'Register and manage facilities',
            self::ManageFacilityAdministrators => 'Manage facility administrator accounts',
            self::ManageSystemSettings => 'Change system settings',
            self::ViewSystemHealth => 'View system health',
            self::ManageVaccineCatalogue => 'Manage the vaccine list',
            self::ViewAuditLogs => 'View the activity log',
            self::PublishCampaigns => 'Publish health campaign messages',
            self::ManageStaff => 'Register and manage health workers',
            self::ManageWards => 'Manage wards and beds',
            self::ManageSchedules => 'Manage doctor schedules',
            self::ManageFacilityProfile => 'Update the facility profile',
            self::ViewFacilityReports => 'View facility reports',
            self::ApproveAppointments => 'Approve or decline appointments',
            self::ViewPatientDemographics => 'Find patients and view personal details',
            self::RegisterPatients => 'Register new patients',
            self::EditPatientDemographics => 'Edit personal details and emergency contacts',
            self::CheckInPatients => 'Check patients in for a visit',
            self::RecordVitals => 'Record vital signs',
            self::ViewVitals => 'View vital signs',
            self::ViewBasicHistory => 'View basic visit history',
            self::ViewFullMedicalRecord => 'View the full medical record',
            self::ConductConsultations => 'Conduct consultations and record diagnoses',
            self::PrescribeMedication => 'Prescribe medication',
            self::AdmitPatients => 'Admit patients',
            self::AllocateBeds => 'Allocate wards and beds',
            self::ViewWardStatus => 'View ward and bed status',
            self::WriteProgressNotes => 'Write inpatient progress notes',
            self::RecordMedicationAdministration => 'Record medication given on the ward',
            self::DischargePatients => 'Discharge patients',
            self::RecordVaccinations => 'Record vaccinations',
            self::ViewVaccinations => 'View vaccination records',
            self::ManageReminders => 'Create medication reminders',
            self::ViewPrescriptions => 'View prescriptions and dosage',
            self::DispenseMedication => 'Dispense medication',
            self::ManageMedicineStock => 'Manage medicine stock',
            self::UsePatientPortal => 'Use the patient portal',
        };
    }
}
