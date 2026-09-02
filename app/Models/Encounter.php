<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'user_id',
        'encounter_type',
        'status',
        'chief_complaint',
        'history_of_present_illness',
        'examination_findings',
        'diagnosis',
        'treatment_plan',
        'requires_admission',
        'encounter_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'datetime',
            'completed_at' => 'datetime',
            'requires_admission' => 'boolean',
        ];
    }

    /**
     * Get the patient for this encounter
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the facility where encounter occurred
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the clinician who conducted the encounter
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get vitals recorded during this encounter
     */
    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class);
    }

    /**
     * Get prescriptions from this encounter
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get admission if patient was admitted
     */
    public function admission(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * Get lab orders for this encounter
     */
    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }
}
