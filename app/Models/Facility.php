<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'facility_code',
        'facility_type',
        'district',
        'region',
        'address',
        'phone_number',
        'email',
        'status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get all users working at this facility
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all patients registered at this facility
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'registered_by_facility_id');
    }

    /**
     * Get all encounters at this facility
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /**
     * Get all admissions at this facility
     */
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * Get all inventory records for this facility
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get all sync queue records for this facility
     */
    public function syncQueue(): HasMany
    {
        return $this->hasMany(SyncQueue::class);
    }
}
