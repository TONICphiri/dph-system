<?php

namespace App\Models;

use App\Enums\CareType;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    protected $fillable = [
        'patient_id',
        'facility_id',
        'checked_in_by',
        'doctor_id',
        'care_type',
        'status',
        'reason_for_visit',
        'history',
        'examination',
        'diagnosis',
        'treatment_plan',
        'checked_in_at',
        'consulted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'care_type' => CareType::class,
            'status' => VisitStatus::class,
            'checked_in_at' => 'datetime',
            'consulted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class)->latest('recorded_at');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function admission(): HasOne
    {
        return $this->hasOne(Admission::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [VisitStatus::Completed, VisitStatus::Cancelled], true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [VisitStatus::Completed, VisitStatus::Cancelled]);
    }

    public function scopeAtFacility(Builder $query, ?int $facilityId): Builder
    {
        return $query->where('facility_id', $facilityId);
    }
}
