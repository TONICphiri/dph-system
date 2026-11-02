<?php

namespace App\Services;

use App\Enums\IdentityStatus;
use App\Enums\PatientStatus;
use App\Enums\RoleName;
use App\Enums\Sex;
use App\Enums\UserStatus;
use App\Exceptions\WorkflowException;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a patient's digital health passport: the patient record, a unique
 * passport number, a QR token, emergency contacts, an optional link to the
 * mother for children, and an optional patient portal account.
 */
class PatientRegistrationService
{
    public function __construct(
        private readonly PassportNumberGenerator $numbers,
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
        private readonly PassportAccessService $access,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated patient fields.
     * @param  array<int, array<string, mixed>>  $contacts  Emergency contacts.
     * @return array{patient: Patient, temporary_password: string|null}
     */
    public function register(
        array $data,
        array $contacts,
        User $worker,
        bool $createPortalAccount,
        bool $emergency = false,
        ?string $emergencyReason = null,
        ?string $emergencyReasonNote = null,
    ): array {
        $isChild = Carbon::parse($data['date_of_birth'])->age < $this->settings->childSeparationAge();

        // The form always posts at least one contact row, even when it is
        // empty. Drop blank rows so the next-of-kin fallback below can apply.
        $contacts = array_values(array_filter($contacts, fn ($contact) => is_array($contact)
            && ! empty($contact['full_name'])
            && ! empty($contact['phone'])));

        if ($emergency) {
            if (empty($emergencyReason)) {
                throw new WorkflowException('Choose why this is an emergency registration.');
            }

            if (empty($data['next_of_kin_name']) || empty($data['next_of_kin_phone'])) {
                throw new WorkflowException('Enter the name and phone number of the next of kin or the person who brought the patient.');
            }

            // The passport is issued now with a provisional identity. The
            // missing details are completed when they become available.
            $data['mother_id'] = null;
            $data['identity_status'] = IdentityStatus::Provisional;
            $data['provisional_reason'] = $emergencyReason;
            $data['provisional_reason_note'] = $emergencyReasonNote;
            $data['provisional_registered_at'] = now();
            $data['provisional_registered_by'] = $worker->id;
            $data['dob_is_estimated'] = (bool) ($data['dob_is_estimated'] ?? false);

            $createPortalAccount = false;
        } else {
            $data['identity_status'] = IdentityStatus::Verified;
            $data['provisional_reason'] = null;
            $data['provisional_reason_note'] = null;
            $data['next_of_kin_name'] = null;
            $data['next_of_kin_relationship'] = null;
            $data['next_of_kin_phone'] = null;
            $data['dob_is_estimated'] = false;
            $data['provisional_registered_at'] = null;
            $data['provisional_registered_by'] = null;

            if ($isChild && empty($data['mother_id'])) {
                throw new WorkflowException('A child must be linked to the mother\'s passport. Scan the mother\'s card first.');
            }

            if (! $isChild) {
                $data['mother_id'] = null;

                if (empty($data['national_id'])) {
                    throw new WorkflowException('An adult must be registered with a National ID.');
                }
            }

            if ($isChild) {
                $this->assertValidMother((int) $data['mother_id'], $worker);
            }
        }

        // The next of kin becomes the first emergency contact when no separate
        // contact was captured at the desk.
        if ($emergency && empty($contacts)) {
            $contacts = [[
                'full_name' => $data['next_of_kin_name'],
                'relationship' => ($data['next_of_kin_relationship'] ?? null) ?: 'Next of kin',
                'phone' => $data['next_of_kin_phone'],
                'physical_address' => null,
            ]];
        }

        $reasonLabel = $emergency
            ? (config('health_passport.emergency_reasons', [])[$emergencyReason] ?? $emergencyReason)
            : null;

        return DB::transaction(function () use ($data, $contacts, $worker, $createPortalAccount, $emergency, $reasonLabel) {
            $patient = Patient::create([
                ...$data,
                'national_id' => filled($data['national_id'] ?? null) ? strtoupper($data['national_id']) : null,
                'passport_number' => $this->numbers->nextPassportNumber(),
                'qr_token' => $this->numbers->newQrToken(),
                'registered_facility_id' => $worker->facility_id,
                'registered_by' => $worker->id,
                'status' => PatientStatus::Active,
            ]);

            $this->syncEmergencyContacts($patient, $contacts);

            $temporaryPassword = null;

            if ($createPortalAccount) {
                $temporaryPassword = $this->createPortalAccount($patient);
            }

            $this->audit->record(
                'patient.registered',
                $emergency
                    ? "Issued provisional passport {$patient->passport_number} to {$patient->full_name} as an emergency registration ({$reasonLabel})."
                    : "Issued passport {$patient->passport_number} to {$patient->full_name}.",
                $patient,
            );

            // The holder is at the desk, so the new passport is open for the worker who issued it.
            $this->access->openAfterRegistration($patient, $worker);

            return ['patient' => $patient, 'temporary_password' => $temporaryPassword];
        });
    }

    /**
     * Link a provisional child to the mother's passport, then complete the
     * identity when nothing else is still estimated.
     */
    public function linkMother(Patient $child, Patient $mother, User $worker): void
    {
        if (! $child->isChild()) {
            throw new WorkflowException('Only a child record can be linked to a mother.');
        }

        if (! $child->isProvisional()) {
            throw new WorkflowException('This passport is already complete.');
        }

        if ($child->mother_id !== null) {
            throw new WorkflowException('This child is already linked to a mother.');
        }

        if ($child->is($mother)) {
            throw new WorkflowException('A child cannot be linked to their own passport.');
        }

        $this->assertValidMother($mother->id, $worker);

        DB::transaction(function () use ($child, $mother, $worker) {
            $child->update(['mother_id' => $mother->id]);

            $this->audit->record(
                'patient.mother-linked',
                "Linked {$child->full_name} to mother {$mother->full_name}.",
                $child,
            );

            $this->completeIdentityIfReady($child->fresh(), $worker);
        });
    }

    /**
     * Mark a provisional record as verified once the missing identity details
     * have been supplied. Adults need a National ID; children need the mother.
     */
    public function completeIdentityIfReady(Patient $patient, User $user): bool
    {
        if (! $patient->isProvisional()) {
            return false;
        }

        if ($patient->dob_is_estimated || $patient->sex === Sex::Unknown) {
            return false;
        }

        if ($patient->isChild()) {
            if ($patient->mother_id === null) {
                return false;
            }
        } elseif (empty($patient->national_id)) {
            return false;
        }

        $patient->update([
            'identity_status' => IdentityStatus::Verified,
            'identity_completed_at' => now(),
            'identity_completed_by' => $user->id,
        ]);

        $this->audit->record('patient.identity-completed', "Completed the identity of {$patient->full_name}.", $patient);

        return true;
    }

    /**
     * Replace the patient's emergency contacts with the submitted list.
     *
     * @param  array<int, array<string, mixed>>  $contacts
     */
    public function syncEmergencyContacts(Patient $patient, array $contacts): void
    {
        $patient->emergencyContacts()->delete();

        foreach (array_values($contacts) as $index => $contact) {
            if (empty($contact['full_name']) || empty($contact['phone'])) {
                continue;
            }

            $patient->emergencyContacts()->create([
                'full_name' => $contact['full_name'],
                'relationship' => $contact['relationship'],
                'phone' => $contact['phone'],
                'physical_address' => $contact['physical_address'] ?? null,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Creates a portal login for the patient and returns the one time password.
     */
    public function createPortalAccount(Patient $patient): string
    {
        if ($patient->isProvisional()) {
            throw new WorkflowException('Complete the identity of the passport holder before creating a portal account.');
        }

        if ($patient->portalAccount()->exists()) {
            throw new WorkflowException('This passport holder already has a portal account.');
        }

        if (empty($patient->email)) {
            throw new WorkflowException('An email address is required to create a portal account.');
        }

        if (User::query()->where('email', $patient->email)->exists()) {
            throw new WorkflowException('The email address is already used by another account.');
        }

        $temporaryPassword = Str::password(10, symbols: false);

        $account = User::create([
            'name' => $patient->full_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'patient_id' => $patient->id,
            'status' => UserStatus::Active,
            'must_change_password' => true,
            'password' => $temporaryPassword,
        ]);

        $account->assignRole(RoleName::Patient->value);

        return $temporaryPassword;
    }

    private function assertValidMother(int $motherId, User $worker): void
    {
        $mother = Patient::query()->find($motherId);

        if (! $mother) {
            throw new WorkflowException('The selected mother could not be found.');
        }

        if (! $this->access->hasOpen($worker, $mother)) {
            throw new WorkflowException('The mother\'s passport is not open. Scan her card again to continue.');
        }

        if ($mother->sex !== Sex::Female) {
            throw new WorkflowException('The linked parent must be the child\'s mother.');
        }

        if ($mother->isChild()) {
            throw new WorkflowException('The mother\'s own passport is still linked as a child record. Please check the details.');
        }
    }
}
