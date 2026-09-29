<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1: passport data model smoke tests (no demo seeding, fast).
 */
class PassportDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_accessor_reports_expired_when_expiry_is_past(): void
    {
        $credential = Credential::factory()->expired()->make();

        $this->assertSame(CredentialStatus::Expired, $credential->effective_status);
    }

    public function test_active_credential_without_expiry_stays_active(): void
    {
        $credential = Credential::factory()->make(['expiry_date' => null, 'status' => 'active']);

        $this->assertSame(CredentialStatus::Active, $credential->effective_status);
        $this->assertTrue($credential->isUsable());
    }

    public function test_qr_token_is_opaque_and_64_chars(): void
    {
        $credential = Credential::factory()->make();

        $this->assertSame(64, strlen($credential->qr_token));
        $this->assertStringNotContainsString($credential->citizen->first_name ?? '', $credential->qr_token);
    }

    public function test_vaccination_credential_creates_vaccination_detail(): void
    {
        $credential = Credential::factory()->create();

        $this->assertNotNull($credential->vaccinationDetail);
        $this->assertSame($credential->id, $credential->vaccinationDetail->credential_id);
    }

    public function test_lab_credential_creates_test_detail_with_expiry(): void
    {
        $credential = Credential::factory()->labTest()->create();

        $this->assertNotNull($credential->testDetail);
        $this->assertNotNull($credential->expiry_date);
    }
}
