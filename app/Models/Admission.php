<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\DischargeOutcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    protected $fillable = [
        'patient_id',
        'visit_id',
        'facility_id',
        'ward_id',
        'bed_id',
        'admitted_by',
        'allocated_by',
        'discharged_by',
        'status',
        'admission_reason',
        'preferred_ward_type',
        'admitted_at',
        'bed_allocated_at',
        'discharged_at',
        'discharge_outcome',
        'discharge_summary',
        'follow_up_instructions',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'discharge_outcome' => DischargeOutcome::class,
            'admitted_at' => 'datetime',
            'bed_allocated_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class)->latest('recorded_at');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(ProgressNote::class)->latest();
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->latest();
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class)->latest('given_at');
    }

    public function lengthOfStayInDays(): int
    {
        $end = $this->discharged_at ?? now();

        return max(1, (int) ceil($this->admitted_at->diffInHours($end) / 24));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereIn('status', [AdmissionStatus::AwaitingBed, AdmissionStatus::Admitted]);
    }
}
