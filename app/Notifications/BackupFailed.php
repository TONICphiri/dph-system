<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class BackupFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $errorMessage,
        protected \DateTimeInterface $ranAt,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠ DHP System — Backup FAILED')
            ->greeting("Hello {$notifiable->name},")
            ->line("The scheduled database backup FAILED on {$this->ranAt->format('M d, Y H:i')}.")
            ->line('This means patient data may currently be unprotected against loss. Please investigate immediately.')
            ->line("Error: {$this->errorMessage}")
            ->line('Check storage/logs/laravel.log for the full trace.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'backup_failed',
            'error' => $this->errorMessage,
            'ran_at' => $this->ranAt->toIso8601String(),
            'message' => "Backup FAILED: {$this->errorMessage}",
        ];
    }
}
