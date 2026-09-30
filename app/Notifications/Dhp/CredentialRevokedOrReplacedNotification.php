<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A credential was revoked or replaced. Number and generic category only;
 * never clinical detail or free-text reasons.
 */
class CredentialRevokedOrReplacedNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $credentialNumber,
        public readonly string $statusLabel,
        public readonly string $reasonCategory,
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
            ->subject('Your Digital Health Passport credential status changed')
            ->line("Credential {$this->credentialNumber} is now {$this->statusLabel} (reason category: {$this->reasonCategory}).")
            ->action('Open My Passport', $this->portalUrl);
    }
}
