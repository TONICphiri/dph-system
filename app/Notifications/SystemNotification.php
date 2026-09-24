<?php

namespace App\Notifications;

use App\Services\SettingService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for notifications shown in the notification centre. When email
 * notifications are switched on in the system settings, a copy is also sent
 * by email to users who have an email address.
 */
abstract class SystemNotification extends Notification
{
    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function category(): string;

    protected function actionUrl(): ?string
    {
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (app(SettingService::class)->get('email_notifications_enabled') === '1' && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'category' => $this->category(),
            'url' => $this->actionUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.$notifiable->name)
            ->line($this->message());

        if ($this->actionUrl()) {
            $mail->action('Open the health passport', $this->actionUrl());
        }

        return $mail;
    }
}
