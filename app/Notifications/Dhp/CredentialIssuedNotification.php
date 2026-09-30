<?php

namespace App\Notifications\Dhp;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A credential was issued. Type, number and dates only; never vaccine,
 * test, result or QR content.
 */
class CredentialIssuedNotification extends DhpMailNotification
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $credentialTypeLabel,
        public readonly string $credentialNumber,
        public readonly string $issueDate,
        public readonly ?string $expiryDate,
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
        $mail = $this->base(new MailMessage, $this->firstName)
            ->subject('A Digital Health Passport credential was issued')
            ->line("A {$this->credentialTypeLabel} credential ({$this->credentialNumber}) was issued on {$this->issueDate}."
                .($this->expiryDate ? " It expires on {$this->expiryDate}." : ' No expiry was recorded.'))
            ->action('Open My Passport', $this->portalUrl);

        return $mail;
    }
}
