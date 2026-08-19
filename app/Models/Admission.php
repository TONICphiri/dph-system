<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'facility_id',
        'ward_name',
        'bed_number',
        'admission_type',
        'admission_reason',
        'admitted_by_user_id',
        'admitted_at',
        'discharge_summary',
        'discharge_status',
        'discharged_by_user_id',
        'discharged_at',
        'follow_up_instructions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    /**
     * Get the encounter for this admission
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /**
     * Get the patient for this admission
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the facility where admitted
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the admitting clinician
     */
    public function admittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by_user_id');
    }

    /**
     * Get the discharging clinician
     */
    public function dischargedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by_user_id');
    }

    /**
     * Get medication administrations during this admission
     */
    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class)->orderByDesc('administered_at');
    }

    /**
     * Get progress notes recorded during this admission
     */
    public function progressNotes(): HasMany
    {
        return $this->hasMany(ProgressNote::class)->orderByDesc('recorded_at');
    }

    /**
     * Calculate length of stay in days
     */
    public function getLengthOfStayAttribute(): ?int
    {
        if (!$this->discharged_at) {
            return null;
        }
        return $this->admitted_at->diffInDays($this->discharged_at);
    }
}
