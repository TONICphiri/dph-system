<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions
        $this->call(RoleAndPermissionSeeder::class);

        // Create test facilities
        $lilongweFacility = Facility::create([
            'name' => 'Lilongwe Central Hospital',
            'facility_code' => 'LLW-001',
            'facility_type' => 'Hospital',
            'district' => 'Lilongwe',
            'region' => 'Central',
            'phone_number' => '+265-1-xxx-xxxx',
            'status' => 'active',
        ]);

        $blantyreFacility = Facility::create([
            'name' => 'Blantyre District Hospital',
            'facility_code' => 'BLY-001',
            'facility_type' => 'Hospital',
            'district' => 'Blantyre',
            'region' => 'Southern',
            'phone_number' => '+265-1-yyy-yyyy',
            'status' => 'active',
        ]);

        $ndirandeFacility = Facility::create([
            'name' => 'Ndirande Health Centre',
            'facility_code' => 'NDR-001',
            'facility_type' => 'Health Centre',
            'district' => 'Blantyre',
            'region' => 'Southern',
            'phone_number' => '+265-1-zzz-zzzz',
            'status' => 'active',
        ]);

        // Create test users with different roles
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@dhp.mw',
            'facility_id' => $lilongweFacility->id,
        ]);
        $adminUser->assignRole('admin');

        $registrationClerk = User::factory()->create([
            'name' => 'John Chirwa',
            'email' => 'clerk@lilongwe.dhp.mw',
            'facility_id' => $lilongweFacility->id,
        ]);
        $registrationClerk->assignRole('registration_clerk');

        $triageNurse = User::factory()->create([
            'name' => 'Mary Nkhata',
            'email' => 'triage@lilongwe.dhp.mw',
            'facility_id' => $lilongweFacility->id,
        ]);
        $triageNurse->assignRole('triage_nurse');

        $clinicalOfficer = User::factory()->create([
            'name' => 'Dr. Samuel Banda',
            'email' => 'doctor@lilongwe.dhp.mw',
            'facility_id' => $lilongweFacility->id,
        ]);
        $clinicalOfficer->assignRole('doctor');

        $pharmacist = User::factory()->create([
            'name' => 'Grace Phiri',
            'email' => 'pharmacy@lilongwe.dhp.mw',
            'facility_id' => $lilongweFacility->id,
        ]);
        $pharmacist->assignRole('pharmacist');

        // Ndirande Health Centre users
        $ndirandeClerk = User::factory()->create([
            'name' => 'Agnes Mthuzi',
            'email' => 'clerk@ndirande.dhp.mw',
            'facility_id' => $ndirandeFacility->id,
        ]);
        $ndirandeClerk->assignRole('registration_clerk');

        $ndirandeNurse = User::factory()->create([
            'name' => 'Francis Mkaka',
            'email' => 'triage@ndirande.dhp.mw',
            'facility_id' => $ndirandeFacility->id,
        ]);
        $ndirandeNurse->assignRole('triage_nurse');

        $ndirandeDoctor = User::factory()->create([
            'name' => 'Dr. Chimwemwe Chilima',
            'email' => 'doctor@ndirande.dhp.mw',
            'facility_id' => $ndirandeFacility->id,
        ]);
        $ndirandeDoctor->assignRole('doctor');

        $ndirandePharmacist = User::factory()->create([
            'name' => 'Rebecca Chiusi',
            'email' => 'pharmacy@ndirande.dhp.mw',
            'facility_id' => $ndirandeFacility->id,
        ]);
        $ndirandePharmacist->assignRole('pharmacist');
    }
}
