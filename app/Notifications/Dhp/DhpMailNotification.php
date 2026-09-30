<?php

namespace App\Notifications\Dhp;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared wording for Digital Health Passport mail.
 */
abstract class DhpMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const PRIVACY_FOOTER = 'This email contains limited account or credential information. Do not forward it if you do not want others to see it.';

    protected function base(MailMessage $mail, string $firstName): MailMessage
    {
        return $mail
            ->greeting("Hello {$firstName},")
            ->line(self::PRIVACY_FOOTER);
    }
}
