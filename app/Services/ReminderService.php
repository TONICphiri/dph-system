<?php

namespace App\Services;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Notifications\ReminderDueNotification;

/**
 * Sends vaccination and medication reminders that are due. Repeating
 * reminders, such as a monthly antiretroviral therapy refill, move to their
 * next due date after sending. Runs every morning from the scheduler.
 */
class ReminderService
{
    public function __construct(private readonly NotificationDispatcher $notifier)
    {
    }

    /**
     * @return int Number of reminders delivered.
     */
    public function sendDue(): int
    {
        $sent = 0;

        Reminder::query()->due()->with('patient.portalAccount', 'patient.mother.portalAccount')
            ->chunkById(200, function ($reminders) use (&$sent) {
                foreach ($reminders as $reminder) {
                    if ($this->notifier->toPatient($reminder->patient, new ReminderDueNotification($reminder))) {
                        $sent++;
                    }

                    $this->advance($reminder);
                }
            });

        return $sent;
    }

    private function advance(Reminder $reminder): void
    {
        if ($reminder->repeat_every_days) {
            $nextDue = $reminder->due_on->copy();

            while ($nextDue->lte(today())) {
                $nextDue->addDays($reminder->repeat_every_days);
            }

            $reminder->update(['due_on' => $nextDue, 'last_sent_at' => now()]);

            return;
        }

        $reminder->update(['last_sent_at' => now(), 'status' => ReminderStatus::Completed]);
    }
}
