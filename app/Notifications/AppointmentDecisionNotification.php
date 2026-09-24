<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class AppointmentDecisionNotification extends SystemNotification
{
    public function __construct(private readonly Appointment $appointment)
    {
    }

    protected function title(): string
    {
        return $this->appointment->status === AppointmentStatus::Approved
            ? 'Appointment approved'
            : 'Appointment declined';
    }

    protected function message(): string
    {
        $appointment = $this->appointment;
        $date = $appointment->appointment_date->format('l j F Y');
        $text = "Your appointment at {$appointment->facility->name} on {$date} has been {$appointment->status->value}.";

        if ($appointment->status === AppointmentStatus::Approved && $appointment->doctor) {
            $text .= " You will be seen by {$appointment->doctor->name}.";
        }

        if ($appointment->decision_note) {
            $text .= ' Note: '.$appointment->decision_note;
        }

        return $text;
    }

    protected function category(): string
    {
        return 'appointment';
    }

    protected function actionUrl(): ?string
    {
        return route('portal.appointments.index');
    }
}
