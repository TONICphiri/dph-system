<?php

namespace App\Notifications;

use App\Models\Appointment;

class NewAppointmentRequestNotification extends SystemNotification
{
    public function __construct(private readonly Appointment $appointment)
    {
    }

    protected function title(): string
    {
        return 'New appointment request';
    }

    protected function message(): string
    {
        return "{$this->appointment->patient->full_name} requested an appointment on {$this->appointment->appointment_date->format('j F Y')}.";
    }

    protected function category(): string
    {
        return 'appointment';
    }

    protected function actionUrl(): ?string
    {
        return route('appointments.index');
    }
}
