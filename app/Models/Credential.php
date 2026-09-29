<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The central object: a verifiable health credential.
 * QR tokens are random and opaque (Str::random(64), NO personal data).
 */
class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'credential_number',
        'citizen_id',
        'facility_id',
        'type',
        'status',
        'issue_date',
        'expiry_date',
        'qr_token',
        'issued_by',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'replaced_by_credential_id',
    ];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return [
            'type' => CredentialType::class,
            'status' => CredentialStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Reports 'expired' whenever expiry_date is in the past,
     * even if the stored status is still active.
     */
    protected function effectiveStatus(): Attribute
    {
        return Attribute::get(function (): CredentialStatus {
            if ($this->status === CredentialStatus::Revoked) {
                return CredentialStatus::Revoked;
            }

            if ($this->status === CredentialStatus::Superseded) {
                return CredentialStatus::Superseded;
            }

            if ($this->expiry_date !== null && $this->expiry_date->isPast()) {
                return CredentialStatus::Expired;
            }

            if ($this->status === CredentialStatus::Expired) {
                return CredentialStatus::Expired;
            }

            return CredentialStatus::Active;
        });
    }

    public function isUsable(): bool
    {
        return $this->effective_status === CredentialStatus::Active;
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_credential_id');
    }

    public function vaccinationDetail(): HasOne
    {
        return $this->hasOne(VaccinationDetail::class);
    }

    public function testDetail(): HasOne
    {
        return $this->hasOne(TestDetail::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }
}
