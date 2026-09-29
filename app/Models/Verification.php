<?php

namespace App\Models;

use App\Enums\VerificationMethod;
use App\Enums\VerificationResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'credential_id',
        'verifier_id',
        'method',
        'result',
        'verified_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'method' => VerificationMethod::class,
            'result' => VerificationResult::class,
            'verified_at' => 'datetime',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }
}
