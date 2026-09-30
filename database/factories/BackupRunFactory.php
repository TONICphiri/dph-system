<?php

namespace Database\Factories;

use App\Models\BackupRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupRun>
 */
class BackupRunFactory extends Factory
{
    protected $model = BackupRun::class;

    public function definition(): array
    {
        return [
            'status' => 'success',
            'archive_size_bytes' => fake()->numberBetween(1024, 10485760),
            'backup_disk' => 'dhp_backups',
            'email_attached' => false,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(5),
        ];
    }
}
