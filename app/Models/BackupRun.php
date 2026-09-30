<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'archive_size_bytes',
        'backup_disk',
        'email_attached',
        'started_at',
        'completed_at',
        'failure_stage',
    ];

    protected function casts(): array
    {
        return [
            'email_attached' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
