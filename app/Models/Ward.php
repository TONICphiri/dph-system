<?php

namespace App\Models;

use App\Enums\BedStatus;
use App\Enums\FacilityStatus;
use App\Enums\WardGender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    protected $fillable = ['facility_id', 'name', 'ward_type', 'gender_restriction', 'status'];

    protected function casts(): array
    {
        return [
            'gender_restriction' => WardGender::class,
            'status' => FacilityStatus::class,
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class)->orderByRaw('LENGTH(bed_number), bed_number');
    }

    public function availableBeds(): HasMany
    {
        return $this->beds()->where('status', BedStatus::Available);
    }
}
