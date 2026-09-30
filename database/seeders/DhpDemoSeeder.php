<?php

namespace Database\Seeders;

use App\Enums\CredentialStatus;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\District;
use App\Models\Facility;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fictional Digital Health Passport demonstration data.
 * All identities, National IDs, emails and facilities are invented for
 * local demo development only. Password for every demo login: password.
 * Skipped entirely when SEED_DEMO_DATA is false. Reset with
 * php artisan migrate:fresh --seed.
 */
class DhpDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('email', 'issuer@example.test')->exists()) {
            $this->command?->warn('DHP demonstration data already exists. Skipped.');

            return;
        }

        $centre = $this->facility('Ndirande Health Centre', 'NDH-D1', 'health_centre', 'Blantyre', true);
        $hospital = $this->facility('Zomba District Hospital', 'ZDH-D1', 'district_hospital', 'Zomba', true);
        $laboratory = $this->facility('Blantyre Central Laboratory', 'BCL-D1', 'laboratory', 'Blantyre', true);
        $this->facility('Closed Rural Post', 'CRP-D1', 'health_centre', 'Mulanje', false);

        $issuer = $this->user('Demo Issuer', 'issuer@example.test', 'issuer', $centre->id);
        $this->user('Demo Verifier', 'verifier@example.test', 'verifier', null);
        $this->user('Demo Administrator', 'admin@example.test', 'admin', null);

        // Citizen without National ID and without an account.
        Citizen::factory()->create([
            'passport_id' => 'MW-DHP-2026-000101',
            'national_id' => null,
            'first_name' => 'Fatsani',
            'last_name' => 'Nkhoma',
            'sex' => 'female',
            'date_of_birth' => '2002-08-14',
            'district' => 'Lilongwe',
            'user_id' => null,
            'created_by' => $issuer->id,
        ]);

        // Non-smartphone citizen: National ID, no portal account.
        $assisted = Citizen::factory()->create([
            'passport_id' => 'MW-DHP-2026-000102',
            'national_id' => 'DEMO0001',
            'first_name' => 'Yamikani',
            'last_name' => 'Phiri',
            'sex' => 'male',
            'date_of_birth' => '1988-03-02',
            'district' => 'Blantyre',
            'phone' => '+265990000001',
            'user_id' => null,
            'created_by' => $issuer->id,
        ]);

        // Smartphone citizen with a linked portal login.
        $portalUser = $this->user('Tadala Mvula', 'citizen@example.test', 'citizen', null);
        $portal = Citizen::factory()->create([
            'passport_id' => 'MW-DHP-2026-000103',
            'national_id' => 'DEMO0002',
            'first_name' => 'Tadala',
            'last_name' => 'Mvula',
            'sex' => 'female',
            'date_of_birth' => '1995-06-20',
            'district' => 'Blantyre',
            'email' => 'citizen@example.test',
            'user_id' => $portalUser->id,
            'created_by' => $issuer->id,
        ]);

        // One credential in each showcase state (all QR tokens random).
        $this->vaccination($portal, $centre->id, $issuer->id, 'MW-CRED-2026-000101', CredentialStatus::Active);
        $this->labTest($portal, $laboratory->id, $issuer->id, 'MW-CRED-2026-000102', CredentialStatus::Active, now()->addDays(30));
        $this->labTest($portal, $laboratory->id, $issuer->id, 'MW-CRED-2026-000103', CredentialStatus::Expired, now()->subDay());
        $this->vaccination($portal, $hospital->id, $issuer->id, 'MW-CRED-2026-000104', CredentialStatus::Revoked);

        // Revoked credential linked to its replacement.
        $old = $this->vaccination($assisted, $centre->id, $issuer->id, 'MW-CRED-2026-000105', CredentialStatus::Superseded);
        $new = $this->vaccination($assisted, $centre->id, $issuer->id, 'MW-CRED-2026-000106', CredentialStatus::Active);
        $old->update(['replaced_by_credential_id' => $new->id]);

        // Safe sample verification + audit rows for the dashboards.
        $valid = Credential::query()->where('credential_number', 'MW-CRED-2026-000101')->firstOrFail();
        Verification::factory()->create(['credential_id' => $valid->id, 'method' => 'qr_scan', 'result' => 'valid']);
        Verification::factory()->create(['credential_id' => null, 'method' => 'manual_code', 'result' => 'not_found']);
        AuditLog::create([
            'user_id' => $issuer->id, 'action' => 'credential_issued', 'entity_type' => 'credential',
            'entity_id' => $valid->id, 'description' => 'credential_issued',
            'details' => ['credential_type' => 'vaccination', 'facility_id' => $centre->id, 'has_expiry' => false],
        ]);
    }

    private function user(string $name, string $email, string $role, ?int $facilityId): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'is_active' => true,
            'status' => 'active',
            'must_change_password' => false,
            'facility_id' => $facilityId,
            'password' => Hash::make('password'),
        ]);

        return $user;
    }

    private function facility(string $name, string $code, string $type, string $district, bool $active): Facility
    {
        return Facility::create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'ownership' => 'Government',
            'district_id' => District::query()->where('name', $district)->value('id'),
            'status' => $active ? 'active' : 'inactive',
            'is_active' => $active,
        ]);
    }

    private function vaccination(Citizen $citizen, int $facilityId, int $issuerId, string $number, CredentialStatus $status): Credential
    {
        $credential = Credential::factory()->create([
            'credential_number' => $number,
            'citizen_id' => $citizen->id,
            'facility_id' => $facilityId,
            'type' => 'vaccination',
            'status' => $status,
            'expiry_date' => null,
            'issued_by' => $issuerId,
            'revoked_at' => in_array($status, [CredentialStatus::Revoked, CredentialStatus::Superseded], true) ? now() : null,
            'revoked_by' => in_array($status, [CredentialStatus::Revoked, CredentialStatus::Superseded], true) ? $issuerId : null,
            'revocation_reason' => $status === CredentialStatus::Revoked ? 'duplicate' : null,
        ]);

        return $credential;
    }

    private function labTest(Citizen $citizen, int $facilityId, int $issuerId, string $number, CredentialStatus $status, \DateTimeInterface $validUntil): Credential
    {
        $credential = Credential::factory()->create([
            'credential_number' => $number,
            'citizen_id' => $citizen->id,
            'facility_id' => $facilityId,
            'type' => 'lab_test',
            'status' => $status,
            'expiry_date' => $validUntil->format('Y-m-d'),
            'issued_by' => $issuerId,
        ]);
        $credential->testDetail()->update(['valid_until' => $validUntil->format('Y-m-d')]);

        return $credential;
    }
}
