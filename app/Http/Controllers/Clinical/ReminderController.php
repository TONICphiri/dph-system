<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Reminder;
use App\Notifications\ReminderScheduledNotification;
use App\Services\AuditLogger;
use App\Services\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reminders set by a health worker, for example a follow up or a monthly
 * antiretroviral therapy refill. Confidential reminders use neutral wording.
 */
class ReminderController extends Controller
{
    public function create(Patient $patient): View
    {
        $this->authorize('manageReminders', $patient);

        return view('clinical.reminder', [
            'patient' => $patient,
            'categories' => ReminderCategory::options(),
        ]);
    }

    public function store(Request $request, Patient $patient, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manageReminders', $patient);

        $data = $request->validate([
            'category' => ['required', Rule::enum(ReminderCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:500'],
            'due_on' => ['required', 'date', 'after_or_equal:today'],
            'repeat_every_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [], ['due_on' => 'first reminder date', 'repeat_every_days' => 'repeat interval']);

        $reminder = $patient->reminders()->create([
            ...$data,
            'is_confidential' => $request->boolean('is_confidential'),
            'status' => ReminderStatus::Active,
            'created_by' => $request->user()->id,
        ]);

        $audit->record('reminder.created', "Set a reminder for {$patient->full_name}.", $reminder);

        return redirect()->route('patients.show', $patient)->with(
            'success',
            'The reminder has been set.'.$this->portalNoticeStatus($patient),
        );
    }

    public function stop(Request $request, Reminder $reminder): RedirectResponse
    {
        $this->authorize('manageReminders', $reminder->patient);

        $reminder->update(['status' => ReminderStatus::Cancelled]);

        return back()->with('success', 'The reminder has been stopped.');
    }

    public function edit(Reminder $reminder): View
    {
        $this->authorize('manageReminders', $reminder->patient);

        return view('clinical.reminder-edit', [
            'patient' => $reminder->patient,
            'reminder' => $reminder,
            'categories' => ReminderCategory::options(),
        ]);
    }

    public function update(Request $request, Reminder $reminder, AuditLogger $audit, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('manageReminders', $reminder->patient);

        $data = $request->validate([
            'category' => ['required', Rule::enum(ReminderCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:500'],
            'due_on' => ['required', 'date', 'after_or_equal:' . ($reminder->due_on?->toDateString() ?? today()->toDateString())],
            'repeat_every_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [], ['due_on' => 'first reminder date', 'repeat_every_days' => 'repeat interval']);

        $reminder->update([
            ...$data,
            'is_confidential' => $request->boolean('is_confidential'),
            'status' => ReminderStatus::Active,
            'last_sent_at' => null,
        ]);

        $audit->record('reminder.updated', "Updated a reminder for {$reminder->patient->full_name}.", $reminder);
        $notifications->toPatient($reminder->patient, new ReminderScheduledNotification($reminder));

        return redirect()->route('patients.show', $reminder->patient)->with(
            'success',
            'The reminder has been updated.'.$this->portalNoticeStatus($reminder->patient),
        );
    }

    private function portalNoticeStatus(Patient $patient): string
    {
        $account = $patient->portalAccount ?? $patient->mother?->portalAccount;

        return $account?->isActive()
            ? ' A notification has been added to the patient portal.'
            : ' No active patient portal account was found, so no portal notification could be added.';
    }
}
