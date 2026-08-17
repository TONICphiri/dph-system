<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncQueue extends Model
{
    use HasFactory;

    protected $table = 'sync_queue';

    protected $fillable = [
        'facility_id',
        'record_type',
        'record_id',
        'action',
        'payload',
        'status',
        'retry_count',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'json',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get the facility for this sync queue record
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Mark as synced
     */
    public function markAsSynced(): void
    {
        $this->update([
            'status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Can retry
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 5; // Max 5 retries
    }
}
