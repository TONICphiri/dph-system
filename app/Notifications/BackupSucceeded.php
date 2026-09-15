<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class BackupSucceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $filename,
        protected float $sizeMb,
        protected \DateTimeInterface $ranAt,
    ) {
    }

    public function via($notifiable): array
    {
        // database = shows a bell/notification-list entry in the app
        // mail = also emails admins so they know even if they don't log in that day
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('DHP System — Backup Completed Successfully')
            ->greeting("Hello {$notifiable->name},")
            ->line("The scheduled database backup completed successfully on {$this->ranAt->format('M d, Y H:i')}.")
            ->line("File: {$this->filename}")
            ->line("Size: {$this->sizeMb} MB")
            ->line('No action is needed.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'backup_succeeded',
            'filename' => $this->filename,
            'size_mb' => $this->sizeMb,
            'ran_at' => $this->ranAt->toIso8601String(),
            'message' => "Backup completed: {$this->filename} ({$this->sizeMb} MB)",
        ];
    }
}
