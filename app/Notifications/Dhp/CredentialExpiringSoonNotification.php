<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A credential expires in 7 days. Number and dates only; never vaccine,
 * test, result or QR content.
 */
class CredentialExpiringSoonNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $credentialTypeLabel,
        public readonly string $credentialNumber,
        public readonly string $expiryDate,
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
            ->subject('Your Digital Health Passport credential is expiring soon')
            ->line("Your {$this->credentialTypeLabel} credential ({$this->credentialNumber}) expires on {$this->expiryDate}. Visit a health facility if you need a new one.")
            ->action('Open My Passport', $this->portalUrl);
    }
}
