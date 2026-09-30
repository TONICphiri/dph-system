<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A citizen kiosk PIN was reset. The PIN itself is never included.
 * (The reset flow arrives in a later phase; this class is ready for it.)
 */
class CitizenPinResetNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $portalUrl,
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
            ->subject('Your Digital Health Passport access PIN was reset')
            ->line('Your access PIN was reset by a health worker. If this was not you, please visit your health facility.')
            ->action('Open My Passport', $this->portalUrl);
    }
}
