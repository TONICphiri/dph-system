<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'facility_id',
        'medication_name',
        'medication_code',
        'strength',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'unit_of_measurement',
        'expiry_date',
        'unit_price',
        'status',
        'notes',
        'last_restocked_at',
        'last_restocked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'last_restocked_at' => 'datetime',
        ];
    }

    /**
     * Get the facility for this inventory
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the user who last restocked
     */
    public function lastRestockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_restocked_by_user_id');
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Update stock status
     */
    public function updateStatus(): void
    {
        if ($this->isExpired()) {
            $this->status = 'expired';
        } elseif ($this->isOutOfStock()) {
            $this->status = 'out_of_stock';
        } elseif ($this->isLowStock()) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'available';
        }
        $this->save();
    }
}
