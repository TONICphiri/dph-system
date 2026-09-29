<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccinationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'credential_id',
        'vaccine_name',
        'dose_number',
        'administration_date',
        'batch_number',
        'next_dose_date',
    ];

    protected function casts(): array
    {
        return [
            'administration_date' => 'date',
            'next_dose_date' => 'date',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
