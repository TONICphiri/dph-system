<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\User;
use App\Services\NinLookupService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        // Create test facilities (idempotent: safe to re-run)
        $lilongweFacility = Facility::firstOrCreate(
            ['facility_code' => 'LLW-001'],
            [
                'name' => 'Lilongwe Central Hospital',
                'facility_type' => 'Hospital',
                'district' => 'Lilongwe',
                'region' => 'Central',
                'phone_number' => '+265-1-xxx-xxxx',
                'status' => 'active',
            ]
        );

        $blantyreFacility = Facility::firstOrCreate(
            ['facility_code' => 'BLY-001'],
            [
                'name' => 'Blantyre District Hospital',
                'facility_type' => 'Hospital',
                'district' => 'Blantyre',
                'region' => 'Southern',
                'phone_number' => '+265-1-yyy-yyyy',
                'status' => 'active',
            ]
        );

        $ndirandeFacility = Facility::firstOrCreate(
            ['facility_code' => 'NDR-001'],
            [
                'name' => 'Ndirande Health Centre',
                'facility_type' => 'Health Centre',
                'district' => 'Blantyre',
                'region' => 'Southern',
                'address' => "Ndirande Township\nP.O. Box 30246, Blantyre, Malawi",
                'phone_number' => '+265 1 870 000',
                'email' => 'ndirande@health.mw',
                'working_hours' => 'Mon–Fri 07:30–17:00 · Ward 24 hrs',
                'status' => 'active',
                'services' => [
                    'Find anyone in seconds',
                    'Scan-and-go identification',
                    'Lifelong visit timeline',
                    'Safe prescribing & dispensing',
                    'Works through blackouts',
                ],
                'departments' => [
                    'Registration',
                    'Triage',
                    'Consultation',
                    'Pharmacy',
                    'Laboratory',
                    'Ward',
                ],
            ]
        );

        // Create test users with different roles (idempotent: safe to re-run)
        $this->seedUser('Admin User', 'admin@dhp.mw', $lilongweFacility->id, 'admin');
        $this->seedUser('John Chirwa', 'clerk@lilongwe.dhp.mw', $lilongweFacility->id, 'registration_clerk');
        $this->seedUser('Mary Nkhata', 'triage@lilongwe.dhp.mw', $lilongweFacility->id, 'triage_nurse');
        $this->seedUser('Dr. Samuel Banda', 'doctor@lilongwe.dhp.mw', $lilongweFacility->id, 'doctor');
        $this->seedUser('Grace Phiri', 'pharmacy@lilongwe.dhp.mw', $lilongweFacility->id, 'pharmacist');

        // Ndirande Health Centre users
        $this->seedUser('Ndirande Facility Admin', 'admin@ndirande.dhp.mw', $ndirandeFacility->id, 'facility_admin');
        $this->seedUser('Agnes Mthuzi', 'clerk@ndirande.dhp.mw', $ndirandeFacility->id, 'registration_clerk');
        $this->seedUser('Francis Mkaka', 'triage@ndirande.dhp.mw', $ndirandeFacility->id, 'triage_nurse');
        $this->seedUser('Dr. Chimwemwe Chilima', 'doctor@ndirande.dhp.mw', $ndirandeFacility->id, 'doctor');
        $this->seedUser('Rebecca Chiusi', 'pharmacy@ndirande.dhp.mw', $ndirandeFacility->id, 'pharmacist');

        // Demo patient account linked to its own clinical file, so the
        // My Records page + 2FA flow can be tried immediately after seeding.
        // NIN: PATIEN-004-99, password: password (2FA enrolled at first visit).
        $demoFile = Patient::firstWhere('dhp_id', 'DHP-2026-00000001')
            ?? Patient::factory()->create([
                'dhp_id' => 'DHP-2026-00000001',
                'first_name' => 'Demo',
                'last_name' => 'Patient',
                'registered_by_facility_id' => $ndirandeFacility->id,
            ]);

        $demoUser = User::firstWhere('email', 'patient@demo.mw');

        if (! $demoUser) {
            $demoUser = User::create([
                'name' => 'Demo Patient',
                'full_name' => 'Demo Patient',
                'email' => 'patient@demo.mw',
                'password' => Hash::make('password'),
                'status' => 'active',
                'must_change_password' => false,
                'facility_id' => $ndirandeFacility->id,
                'nin_hash' => NinLookupService::hash('PATIEN-004-99'),
                'nin_last4' => NinLookupService::last4('PATIEN-004-99'),
                'patient_id' => $demoFile->id,
            ]);
        } else {
            $demoUser->forceFill(['patient_id' => $demoFile->id])->saveQuietly();
        }

        if (! $demoUser->hasRole('patient')) {
            $demoUser->assignRole('patient');
        }

        // Seed sample guardians for the pediatric flow
        Guardian::firstOrCreate(
            ['national_id' => 'A765432101'],
            [
                'first_name' => 'Grace',
                'last_name' => 'Phiri',
                'phone_number' => '+265 888 765 432',
                'relationship' => 'Mother',
                'status' => 'active',
            ]
        );

        Guardian::firstOrCreate(
            ['national_id' => 'A123987654'],
            [
                'first_name' => 'James',
                'last_name' => 'Mbewe',
                'phone_number' => '+265 999 123 456',
                'relationship' => 'Father',
                'status' => 'active',
            ]
        );
    }

    /**
     * Create a seed user only if the email is not taken, then ensure
     * the role is assigned. Makes `db:seed` safe to run repeatedly.
     */
    protected function seedUser(string $name, string $email, int $facilityId, string $role): User
    {
        $user = User::firstWhere('email', $email);

        if (!$user) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'facility_id' => $facilityId,
            ]);
        }

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }
}
