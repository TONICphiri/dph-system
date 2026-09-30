<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A backup process failed. Generic operational detail only; never file
 * paths, data, tokens or stack traces. (Triggered by Phase 8 work.)
 */
class BackupFailedNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $failedAt,
        public readonly string $reference,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->base(new MailMessage, $this->firstName)
            ->subject('Digital Health Passport backup alert')
            ->line("The scheduled backup did not complete at {$this->failedAt} (reference {$this->reference}). Please check the server.");
    }
}
