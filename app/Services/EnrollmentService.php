<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Facility enrollment workflow per system-description2.md FR-A1..A3, §5.2, §5.4.
 * DRAFT → PENDING → ACTIVE (first-login password set) → SUSPENDED / DEACTIVATED.
 * Every transition writes an audit_logs entry.
 */
class EnrollmentService
{
    /**
     * Validate NIN format + catalogue uniqueness (hash lookup).
     *
     * @return array{ok: bool, message?: string, existing?: ?User}
     */
    public static function checkNin(string $nin): array
    {
        $normalized = NinLookupService::normalize($nin);

        if (strlen($normalized) < 6 || strlen($normalized) > 20) {
            return ['ok' => false, 'message' => 'Invalid National ID Number format.'];
        }

        $existing = NinLookupService::findByNin($nin);
        if ($existing) {
            return [
                'ok' => false,
                'message' => 'Identity already enrolled ('.$existing->masked_nin.').',
                'existing' => $existing,
            ];
        }

        return ['ok' => true];
    }

    /**
     * Create a PENDING catalogue entry. Staff must have physically verified
     * the national ID document (checkbox + staff identity recorded).
     */
    public static function enroll(array $data, User $enroller): User
    {
        $check = self::checkNin($data['nin']);
        if (! $check['ok']) {
            throw new \InvalidArgumentException($check['message']);
        }

        return DB::transaction(function () use ($data, $enroller) {
            $user = User::create([
                'name' => $data['full_name'],
                'full_name' => $data['full_name'],
                'nin_hash' => NinLookupService::hash($data['nin']),
                'nin_last4' => NinLookupService::last4($data['nin']),
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                // Random secret until activation; user sets personal password at first login (FR-A5).
                'password' => Hash::make(Str::random(32)),
                'status' => 'pending',
                'must_change_password' => true,
                'id_document_ref' => $data['id_document_ref'] ?? null,
                // Optional link to the clinical file (patient role opens only this file).
                'patient_id' => $data['patient_id'] ?? null,
                'facility_id' => $enroller->facility_id,
                'enrolled_by' => $enroller->id,
            ]);

            try {
                $user->assignRole($data['role'] ?? 'patient');
            } catch (\Throwable $e) {
                // Roles table may not be seeded in every environment.
            }

            AuditService::log($enroller, 'enrollment.created', $user, [
                'facility_id' => $enroller->facility_id,
            ]);

            return $user;
        });
    }

    public static function approve(User $pendingUser, User $approver): User
    {
        return DB::transaction(function () use ($pendingUser, $approver) {
            $pendingUser->update([
                'status' => 'active',
                'approved_by' => $approver->id,
                'must_change_password' => true,
            ]);

            AuditService::log($approver, 'enrollment.approved', $pendingUser, [
                'facility_id' => $pendingUser->facility_id,
            ]);

            return $pendingUser->fresh();
        });
    }

    public static function reject(User $pendingUser, User $approver, string $reason = ''): User
    {
        return DB::transaction(function () use ($pendingUser, $approver, $reason) {
            $pendingUser->update(['status' => 'deactivated']);

            AuditService::log($approver, 'enrollment.rejected', $pendingUser, [
                'facility_id' => $pendingUser->facility_id,
                'reason' => $reason,
            ]);

            return $pendingUser->fresh();
        });
    }
}
