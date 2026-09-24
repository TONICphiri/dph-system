<?php

namespace App\Models;

use App\Enums\CampaignAudience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCampaign extends Model
{
    protected $fillable = ['facility_id', 'title', 'category', 'message', 'audience', 'published_at', 'recipients_count', 'created_by'];

    protected function casts(): array
    {
        return [
            'audience' => CampaignAudience::class,
            'published_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
