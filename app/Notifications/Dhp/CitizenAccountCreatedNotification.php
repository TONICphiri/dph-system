<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Welcome a new citizen portal account. Carries a one-time set-password
 * link only; never a password, and no identity or health data.
 */
class CitizenAccountCreatedNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $resetUrl,
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
            ->subject('Set up your Digital Health Passport account')
            ->line('A Digital Health Passport portal account was created for you. It is pending activation until you choose a password.')
            ->action('Set password', $this->resetUrl)
            ->line('If you did not expect this account, please contact your health facility.');
    }
}
