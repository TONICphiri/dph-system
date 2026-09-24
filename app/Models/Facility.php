<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'ownership',
        'district_id',
        'physical_address',
        'phone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FacilityStatus::class,
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }

    public function beds(): HasManyThrough
    {
        return $this->hasManyThrough(Bed::class, Ward::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'registered_facility_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function isActive(): bool
    {
        return $this->status === FacilityStatus::Active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', FacilityStatus::Active);
    }
}
