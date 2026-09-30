<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tell a staff member their account is pending activation, with a
 * one-time set-password link. No password is ever included.
 */
class StaffAccountCreatedNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $roleLabel,
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
            ->subject('Set up your Digital Health Passport staff account')
            ->line("A Digital Health Passport {$this->roleLabel} account was created for you. It is pending activation until you choose a password.")
            ->action('Set password', $this->resetUrl)
            ->line('If you did not expect this account, please contact your administrator.');
    }
}
