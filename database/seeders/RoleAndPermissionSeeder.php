<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Define all permissions
        $permissions = [
            // Patient permissions
            'create_patient',
            'view_patients',
            'view_patient',
            'edit_patient',
            'delete_patient',

            // Encounter permissions
            'create_encounter',
            'view_encounters',
            'view_encounter',
            'edit_encounter',

            // Triage permissions
            'record_vitals',
            'view_vitals',
            'triage_patient',

            // Consultation permissions
            'create_consultation',
            'view_consultations',
            'prescribe_medication',
            'consult_patient',

            // Pharmacy permissions
            'dispense_medication',
            'view_prescriptions',
            'manage_inventory',

            // Admission permissions
            'admit_patient',
            'manage_admissions',
            'discharge_patient',

            // Patient permissions
            'update_patient',

            // Sync permissions
            'view_sync_queue',
            'upload_sync',

            // Facility management
            'manage_facility',
            'manage_facility_users',

            // Reports
            'view_reports',
            'generate_reports',

            // Admin
            'manage_permissions',
            'view_audit_logs',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles and their permissions
        $roles = [
            'admin' => $permissions, // Admin has all permissions

            'registration_clerk' => [
                'create_patient',
                'view_patients',
                'view_patient',
                'edit_patient',
                'create_encounter',
            ],

            'triage_nurse' => [
                'view_patients',
                'view_patient',
                'view_encounters',
                'create_encounter',
                'record_vitals',
                'view_vitals',
                'triage_patient',
            ],

            'clinical_officer' => [
                'view_patients',
                'view_patient',
                'view_encounters',
                'view_encounter',
                'create_consultation',
                'view_consultations',
                'prescribe_medication',
                'admit_patient',
                'view_prescriptions',
                'record_vitals',
                'view_vitals',
                'consult_patient',
                'update_patient',
                'view_sync_queue',
            ],

            'doctor' => [
                'view_patients',
                'view_patient',
                'edit_patient',
                'view_encounters',
                'view_encounter',
                'create_consultation',
                'view_consultations',
                'prescribe_medication',
                'admit_patient',
                'discharge_patient',
                'manage_admissions',
                'view_prescriptions',
                'record_vitals',
                'view_vitals',
                'view_reports',
                'consult_patient',
                'update_patient',
                'view_sync_queue',
                'upload_sync',
            ],

            'pharmacist' => [
                'view_patients',
                'view_patient',
                'dispense_medication',
                'view_prescriptions',
                'manage_inventory',
                'view_reports',
                'view_sync_queue',
            ],

            'ward_nurse' => [
                'view_patients',
                'view_patient',
                'manage_admissions',
                'view_prescriptions',
                'record_vitals',
                'view_vitals',
                'update_patient',
                'view_reports',
                'view_sync_queue',
            ],

            'hospital_administrator' => [
                'view_patients',
                'view_encounters',
                'manage_facility',
                'manage_facility_users',
                'view_reports',
                'generate_reports',
                'view_audit_logs',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
