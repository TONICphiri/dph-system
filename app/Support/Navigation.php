<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;

/**
 * Builds the sidebar menu for the signed in user. Each link is shown only
 * when the user holds the permission that protects its route, so the menu
 * always matches what the user is allowed to open.
 */
class Navigation
{
    /**
     * @return array<int, array{heading: string, items: array<int, array{label: string, route: string, icon: string, active: string}>}>
     */
    public static function for(User $user): array
    {
        $sections = [
            'Overview' => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard', null],
            ],
            'Platform' => [
                ['Facilities', 'admin.facilities.index', 'building', 'admin.facilities.*', Permission::ManageFacilities],
                ['Facility administrators', 'admin.facility-administrators.index', 'users', 'admin.facility-administrators.*', Permission::ManageFacilityAdministrators],
                ['Vaccine list', 'admin.vaccines.index', 'syringe', 'admin.vaccines.*', Permission::ManageVaccineCatalogue],
                ['Districts', 'admin.districts.index', 'map', 'admin.districts.*', Permission::ManageSystemSettings],
                ['System settings', 'admin.settings.edit', 'settings', 'admin.settings.*', Permission::ManageSystemSettings],
                ['System health', 'admin.system-health', 'activity', 'admin.system-health', Permission::ViewSystemHealth],
            ],
            'Facility' => [
                ['Health workers', 'facility.staff.index', 'users', 'facility.staff.*', Permission::ManageStaff],
                ['Wards and beds', 'facility.wards.index', 'door', 'facility.wards.*', Permission::ManageWards],
                ['Doctor schedules', 'facility.schedules.index', 'clock', 'facility.schedules.*', Permission::ManageSchedules],
                ['Facility profile', 'facility.profile.edit', 'building', 'facility.profile.*', Permission::ManageFacilityProfile],
                ['Reports', 'facility.reports', 'chart', 'facility.reports', Permission::ViewFacilityReports],
            ],
            'Patients' => [
                ['Find a patient', 'patients.index', 'search', 'patients.index', Permission::ViewPatientDemographics],
                ['Scan passport card', 'patients.scan', 'qr', 'patients.scan', Permission::ViewPatientDemographics],
                ['Register a patient', 'patients.create', 'user-plus', 'patients.create', Permission::RegisterPatients],
                ['Patient queue', 'visits.queue', 'list', 'visits.queue', [Permission::CheckInPatients, Permission::RecordVitals, Permission::ConductConsultations]],
            ],
            'Inpatient care' => [
                ['Admissions', 'admissions.index', 'bed', 'admissions.*', Permission::ViewWardStatus],
                ['Bed board', 'facility.bed-board', 'dashboard', 'facility.bed-board', Permission::ViewWardStatus],
            ],
            'Services' => [
                ['Appointments', 'appointments.index', 'calendar', 'appointments.*', Permission::ApproveAppointments],
                ['Pharmacy', 'pharmacy.index', 'pill', 'pharmacy.*', Permission::DispenseMedication],
                ['Medicine stock', 'medicines.index', 'box', 'medicines.*', Permission::ManageMedicineStock],
                ['Health campaigns', 'campaigns.index', 'megaphone', 'campaigns.*', Permission::PublishCampaigns],
                ['Activity log', 'audit-log.index', 'shield', 'audit-log.*', Permission::ViewAuditLogs],
            ],
            'My health' => [
                ['My records', 'portal.records', 'heart', 'portal.records', Permission::UsePatientPortal],
                ['My passport card', 'portal.card', 'qr', 'portal.card', Permission::UsePatientPortal],
                ['My appointments', 'portal.appointments.index', 'calendar', 'portal.appointments.*', Permission::UsePatientPortal],
            ],
            'Account' => [
                ['Notifications', 'notifications.index', 'bell', 'notifications.*', null],
                ['My profile', 'profile.edit', 'user', 'profile.*', null],
                ['Future features', 'roadmap', 'road', 'roadmap', null],
            ],
        ];

        $menu = [];

        foreach ($sections as $heading => $links) {
            $items = [];

            foreach ($links as [$label, $route, $icon, $active, $permission]) {
                if (self::allowed($user, $permission)) {
                    $items[] = compact('label', 'route', 'icon', 'active');
                }
            }

            if ($items !== []) {
                $menu[] = ['heading' => $heading, 'items' => $items];
            }
        }

        return $menu;
    }

    private static function allowed(User $user, Permission|array|null $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        $names = array_map(fn (Permission $item) => $item->value, is_array($permission) ? $permission : [$permission]);

        return $user->hasAnyPermission($names);
    }
}
