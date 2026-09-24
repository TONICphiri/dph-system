<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'facility_id',
        'doctor_id',
        'appointment_date',
        'reason',
        'status',
        'decision_note',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'decided_at' => 'datetime',
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

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(AppointmentReview::class);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === AppointmentStatus::Completed && ! $this->review;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [AppointmentStatus::Pending, AppointmentStatus::Approved], true)
            && $this->appointment_date->isFuture();
    }
}
