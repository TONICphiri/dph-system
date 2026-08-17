<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'national_id',
        'dhp_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone_number',
        'address',
        'village',
        'district',
        'status',
        'is_child',
        'guardian_id',
        'registered_at',
        'registered_by_facility_id',
        'registered_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registered_at' => 'datetime',
            'is_child' => 'boolean',
        ];
    }

    /**
     * Get the facility where patient was registered
     */
    public function registeredByFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'registered_by_facility_id');
    }

    /**
     * Get the user who registered the patient
     */
    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    /**
     * Get the guardian if this is a child patient
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get all encounters for this patient
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class)->orderByDesc('encounter_date');
    }

    /**
     * Get all vitals for this patient
     */
    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class)->orderByDesc('recorded_at');
    }

    /**
     * Get all prescriptions for this patient
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->orderByDesc('prescribed_at');
    }

    /**
     * Get all admissions for this patient
     */
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class)->orderByDesc('admitted_at');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get age in years
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return $this->date_of_birth->diffInYears(now());
    }

    /**
     * Generate DHP ID if not exists
     */
    public static function generateDhpId(): string
    {
        $year = now()->year;
        $latestPatient = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();
        
        $sequence = ($latestPatient ? intval(substr($latestPatient->dhp_id, -8)) : 0) + 1;
        return sprintf('DHP-%d-%08d', $year, $sequence);
    }
}
