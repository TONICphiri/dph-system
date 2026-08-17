<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'national_id',
        'phone_number',
        'address',
        'relationship',
        'status',
    ];

    /**
     * Get all children linked to this guardian
     */
    public function children(): HasMany
    {
        return $this->hasMany(Patient::class, 'guardian_id');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
