<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'facility_id',
        'status',
        'nin_hash',
        'nin_last4',
        'dob',
        'gender',
        'phone',
        'id_document_ref',
        'enrolled_by',
        'approved_by',
        'patient_id',
        'must_change_password',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'nin_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'must_change_password' => 'boolean',
            'locked_until' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Catalogue display name: prefer full_name, fall back to legacy name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->name ?: 'Unknown';
    }

    /**
     * Masked NIN for all UI/lists/logs per NFR-7 (e.g. NIN-****-4821).
     */
    public function getMaskedNinAttribute(): string
    {
        return $this->nin_last4 ? 'NIN-****-'.$this->nin_last4 : 'NIN-****-----';
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'enrolled_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    /**
     * The clinical file belonging to this account (patient role only).
     * Set explicitly by facility staff — never guessed by name matching.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function ownsPatient(Patient $patient): bool
    {
        return $this->patient_id !== null && (int) $this->patient_id === (int) $patient->getKey();
    }

    public function hasTwoFactor(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Get the facility where this user works
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * National Admin operates system-wide: MySQL queries carry no
     * facility_id constraint, granting global read/write access.
     * Spec roles super_admin/system_admin (§4.1) plus legacy admin are national.
     */
    public function isNationalAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'national_admin', 'super_admin', 'system_admin']);
    }

    /**
     * Facility Admin is strictly scoped to their assigned location.
     */
    public function isFacilityAdmin(): bool
    {
        return $this->hasRole('facility_admin');
    }

    /**
     * True when this user may only see data from their own facility.
     */
    public function isFacilityScoped(): bool
    {
        return !$this->isNationalAdmin();
    }

    /**
     * Constrain a query to the user's facility unless they are a
     * National Admin. National Admins see every row (no constraint).
     */
    public function scopeToFacility($query, string $column = 'facility_id')
    {
        if ($this->isNationalAdmin()) {
            return $query;
        }

        return $query->where($column, $this->facility_id);
    }
}
