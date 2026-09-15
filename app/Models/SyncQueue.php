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
        'next_retry_at',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'json',
            'synced_at' => 'datetime',
            'next_retry_at' => 'datetime',
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
            'next_retry_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Mark as failed with exponential backoff (1, 2, 4, 8, 16 min).
     * The record stays retryable so it uploads on its own once the
     * internet is restored — staff never retry by hand.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $attempts = $this->retry_count + 1;

        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $attempts,
            'next_retry_at' => $attempts < 5
                ? now()->addMinutes(min(16, 2 ** ($attempts - 1)))
                : null,
        ]);
    }

    /**
     * Records due for upload: fresh pending rows plus failed rows whose
     * backoff has expired and which still have retries left.
     */
    public function scopeDueForSync($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'pending')
                ->orWhere(function ($q) {
                    $q->where('status', 'failed')
                        ->where('retry_count', '<', 5)
                        ->where(function ($q) {
                            $q->whereNull('next_retry_at')
                                ->orWhere('next_retry_at', '<=', now());
                        });
                });
        });
    }

    /**
     * Can retry
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 5; // Max 5 retries
    }
}
