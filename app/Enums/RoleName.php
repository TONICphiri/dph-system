<?php

namespace App\Enums;

/**
 * The seven user roles in the system.
 */
enum RoleName: string
{
    use HasOptions;

    case SystemAdmin = 'system_admin';
    case FacilityAdmin = 'facility_admin';
    case Clerk = 'clerk';
    case Nurse = 'nurse';
    case Doctor = 'doctor';
    case Pharmacist = 'pharmacist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Administrator',
            self::FacilityAdmin => 'Facility Administrator',
            self::Clerk => 'Clerk',
            self::Nurse => 'Nurse',
            self::Doctor => 'Doctor',
            self::Pharmacist => 'Pharmacist',
            self::Patient => 'Patient',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::SystemAdmin => 'neutral',
            self::FacilityAdmin => 'neutral',
            self::Clerk => 'neutral',
            self::Nurse => 'neutral',
            self::Doctor => 'neutral',
            self::Pharmacist => 'neutral',
            self::Patient => 'neutral',
        };
    }

    /**
     * Roles a Facility Administrator may create for their own facility.
     *
     * @return array<int, self>
     */
    public static function facilityStaffRoles(): array
    {
        return [self::Clerk, self::Nurse, self::Doctor, self::Pharmacist];
    }

    /**
     * Roles that belong to a facility and must have a facility assigned.
     */
    public function belongsToFacility(): bool
    {
        return ! in_array($this, [self::SystemAdmin, self::Patient], true);
    }

    /**
     * The permissions granted to this role. This is the single source of
     * truth for role based access and is written to the database by the seeder.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SystemAdmin => [
                Permission::ManageFacilities,
                Permission::ManageFacilityAdministrators,
                Permission::ManageSystemSettings,
                Permission::ViewSystemHealth,
                Permission::ManageVaccineCatalogue,
                Permission::ViewAuditLogs,
                Permission::PublishCampaigns,
            ],
            self::FacilityAdmin => [
                Permission::ManageStaff,
                Permission::ManageWards,
                Permission::ManageSchedules,
                Permission::ManageFacilityProfile,
                Permission::ViewFacilityReports,
                Permission::ApproveAppointments,
                Permission::AllocateBeds,
                Permission::ViewWardStatus,
                Permission::ViewAuditLogs,
                Permission::PublishCampaigns,
                Permission::ManageMedicineStock,
            ],
            self::Clerk => [
                Permission::ViewPatientDemographics,
                Permission::RegisterPatients,
                Permission::EditPatientDemographics,
                Permission::CheckInPatients,
            ],
            self::Nurse => [
                Permission::ViewPatientDemographics,
                Permission::RecordVitals,
                Permission::ViewVitals,
                Permission::ViewBasicHistory,
                Permission::AllocateBeds,
                Permission::ViewWardStatus,
                Permission::WriteProgressNotes,
                Permission::RecordMedicationAdministration,
                Permission::RecordVaccinations,
                Permission::ViewVaccinations,
            ],
            self::Doctor => [
                Permission::ViewPatientDemographics,
                Permission::RecordVitals,
                Permission::ViewVitals,
                Permission::ViewBasicHistory,
                Permission::ViewFullMedicalRecord,
                Permission::ConductConsultations,
                Permission::PrescribeMedication,
                Permission::AdmitPatients,
                Permission::ViewWardStatus,
                Permission::WriteProgressNotes,
                Permission::DischargePatients,
                Permission::RecordVaccinations,
                Permission::ViewVaccinations,
                Permission::ManageReminders,
                Permission::ViewPrescriptions,
                Permission::ApproveAppointments,
            ],
            self::Pharmacist => [
                Permission::ViewPrescriptions,
                Permission::DispenseMedication,
                Permission::ManageMedicineStock,
            ],
            self::Patient => [
                Permission::UsePatientPortal,
            ],
        };
    }
}
