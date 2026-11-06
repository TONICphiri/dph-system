<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Notification;

/**
 * An in-portal notice that a health worker added or changed a reminder.
 */
class ReminderScheduledNotification extends Notification
{
    public function __construct(private readonly Reminder $reminder)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->reminder->is_confidential) {
            return [
                'title' => 'Health reminder added',
                'message' => 'A confidential health reminder has been added to your passport. Sign in to view it.',
                'category' => 'reminder',
                'url' => route('portal.records', ['patient' => $this->reminder->patient_id]),
            ];
        }

        return [
            'title' => $this->reminder->title,
            'message' => $this->reminder->message.' Due on '.$this->reminder->due_on->format('j F Y').'.',
            'category' => $this->reminder->category->value,
            'url' => route('portal.records', ['patient' => $this->reminder->patient_id]),
        ];
    }
}
