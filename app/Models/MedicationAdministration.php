<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'prescription_id',
        'patient_id',
        'administered_by_user_id',
        'medication_name',
        'dose',
        'route',
        'administered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_at' => 'datetime',
        ];
    }

    /**
     * Get the admission for this administration
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the prescription this administration relates to
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the patient for this administration
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who administered the medication
     */
    public function administeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by_user_id');
    }
}