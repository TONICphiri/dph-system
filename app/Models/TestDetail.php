<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'credential_id',
        'test_type',
        'sample_collection_date',
        'result_date',
        'result',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'sample_collection_date' => 'date',
            'result_date' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
