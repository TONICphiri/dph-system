<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'prescribed_by_user_id',
        'medication_name',
        'medication_code',
        'dose',
        'frequency',
        'quantity',
        'duration',
        'instructions',
        'status',
        'dispensed_by_user_id',
        'dispensed_at',
        'notes',
        'prescribed_at',
    ];

    protected function casts(): array
    {
        return [
            'prescribed_at' => 'datetime',
            'dispensed_at' => 'datetime',
        ];
    }

    /**
     * Get the encounter for this prescription
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /**
     * Get the patient for this prescription
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the prescribing clinician
     */
    public function prescribedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by_user_id');
    }

    /**
     * Get the dispensing pharmacist
     */
    public function dispensedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by_user_id');
    }
}
