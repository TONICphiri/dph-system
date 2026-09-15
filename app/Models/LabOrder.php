<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'test_type',
        'test_name',
        'status',
        'description',
        'requested_by_user_id',
        'requested_at',
        'completed_at',
        'result_description',
        'result_value',
        'result_units',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'encounter_id' => 'integer',
        'requested_by_user_id' => 'integer',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the patient for this lab order
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the encounter for this lab order
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /**
     * Get the user who requested the lab test
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Scope lab orders by status
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['requested', 'pending']);
    }

    /**
     * Scope lab orders with results
     */
    public function scopeWithResults($query)
    {
        return $query->whereNotNull('result_value');
    }
}