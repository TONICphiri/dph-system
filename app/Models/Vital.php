<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'temperature',
        'systolic_bp',
        'diastolic_bp',
        'heart_rate',
        'respiratory_rate',
        'weight',
        'height',
        'muac',
        'oxygen_saturation',
        'priority_level',
        'notes',
        'recorded_by_user_id',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the encounter for this vital
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /**
     * Get the patient for this vital
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who recorded this vital
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Calculate BMI (Body Mass Index)
     * Note: Height should be in cm, weight in kg
     */
    public function getBmiAttribute(): ?float
    {
        if (!$this->weight || !$this->height || $this->height == 0) {
            return null;
        }
        $heightInMeters = $this->height / 100;
        return round($this->weight / ($heightInMeters ** 2), 2);
    }

    /**
     * Priority level ordering (highest priority first)
     */
    public const PRIORITY_ORDER = ['Emergency', 'High', 'Medium', 'Low'];

    /**
     * Check if vitals are abnormal
     */
    public function isAbnormal(): bool
    {
        // Temperature: Normal is 36.5-37.5°C
        if ($this->temperature && ($this->temperature < 36 || $this->temperature > 38.5)) {
            return true;
        }

        // Systolic BP: Normal < 120
        if ($this->systolic_bp && $this->systolic_bp > 140) {
            return true;
        }

        // Heart Rate: Normal 60-100 bpm
        if ($this->heart_rate && ($this->heart_rate < 50 || $this->heart_rate > 110)) {
            return true;
        }

        // Respiratory Rate: Normal 12-20
        if ($this->respiratory_rate && ($this->respiratory_rate < 10 || $this->respiratory_rate > 25)) {
            return true;
        }

        // SpO2: Normal > 94%
        if ($this->oxygen_saturation && $this->oxygen_saturation < 90) {
            return true;
        }

        return false;
    }

    /**
     * Determine priority level automatically from vitals
     */
    public function autoPriorityLevel(): string
    {
        // SpO2 below 90 is critical - Emergency
        if ($this->oxygen_saturation && $this->oxygen_saturation < 90) {
            return 'Emergency';
        }

        // Temperature > 39 or < 35 is high risk
        if ($this->temperature && ($this->temperature > 39 || $this->temperature < 35)) {
            return 'High';
        }

        // Systolic BP > 160 is high risk
        if ($this->systolic_bp && $this->systolic_bp > 160) {
            return 'High';
        }

        // Heart rate < 45 or > 130 is high risk
        if ($this->heart_rate && ($this->heart_rate < 45 || $this->heart_rate > 130)) {
            return 'High';
        }

        // Respiratory rate < 8 or > 30 is high risk
        if ($this->respiratory_rate && ($this->respiratory_rate < 8 || $this->respiratory_rate > 30)) {
            return 'High';
        }

        // Any other abnormal reading is medium priority
        if ($this->isAbnormal()) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Get a numeric weight for queue ordering (lower is higher priority)
     */
    public function getPriorityWeightAttribute(): int
    {
        return array_search($this->priority_level, self::PRIORITY_ORDER) ?? count(self::PRIORITY_ORDER);
    }
}
