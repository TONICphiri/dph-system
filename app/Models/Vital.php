<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    protected $fillable = [
        'patient_id',
        'visit_id',
        'admission_id',
        'temperature',
        'weight',
        'height',
        'systolic_pressure',
        'diastolic_pressure',
        'pulse_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:1',
            'weight' => 'decimal:1',
            'height' => 'decimal:1',
            'recorded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bloodPressure(): ?string
    {
        if (! $this->systolic_pressure || ! $this->diastolic_pressure) {
            return null;
        }

        return "{$this->systolic_pressure}/{$this->diastolic_pressure}";
    }
}
