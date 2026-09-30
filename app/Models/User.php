<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'job_title',
        'professional_registration_number',
        'facility_id',
        'patient_id',
        'status',
        'must_change_password',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => \App\Enums\DhpRole::class,
            'is_active' => 'boolean',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function citizen(): HasMany
    {
        return $this->hasMany(Citizen::class, 'user_id');
    }

    /**
     * The single citizen profile linked to this login, if any.
     * One citizen user links to only one citizen record
     * (enforced by the unique constraint on citizens.user_id).
     */
    public function citizenProfile(): HasOne
    {
        return $this->hasOne(Citizen::class, 'user_id');
    }

    public function createdCitizens(): HasMany
    {
        return $this->hasMany(Citizen::class, 'created_by');
    }

    public function issuedCredentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'issued_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AppointmentReview::class, 'doctor_id');
    }

    /**
     * The user's role as an enum. Every account holds exactly one role.
     */
    public function role(): ?RoleName
    {
        $name = $this->getRoleNames()->first();

        return $name ? RoleName::tryFrom($name) : null;
    }

    public function roleLabel(): string
    {
        return $this->role()?->label() ?? 'No role';
    }

    public function isRole(RoleName $role): bool
    {
        return $this->role() === $role;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * True when this user works at the given facility.
     */
    public function worksAt(?int $facilityId): bool
    {
        return $facilityId !== null && $this->facility_id === $facilityId;
    }

    /**
     * True when this portal user is the patient or the patient's mother.
     */
    public function ownsPatientRecord(Patient $patient): bool
    {
        if (! $this->patient_id) {
            return false;
        }

        return $patient->id === $this->patient_id || $patient->mother_id === $this->patient_id;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Active);
    }

    /**
     * Active DHP administrators with an email address (backup alerts).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function dhpAdminsWithEmail()
    {
        return self::query()
            ->where('role', \App\Enums\DhpRole::Admin)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();
    }

    public function scopeWithRole(Builder $query, RoleName $role): Builder
    {
        return $query->role($role->value);
    }
}
