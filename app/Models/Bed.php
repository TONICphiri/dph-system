<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends Model
{
    protected $fillable = ['ward_id', 'bed_number', 'status'];

    protected function casts(): array
    {
        return ['status' => BedStatus::class];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function currentAdmission(): HasOne
    {
        return $this->hasOne(Admission::class)->where('status', AdmissionStatus::Admitted);
    }
}
