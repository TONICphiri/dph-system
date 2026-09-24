<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\WorkflowException;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentDecisionNotification;
use App\Notifications\NewAppointmentRequestNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Appointment booking with doctor availability, approval and reviews.
 */
class AppointmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationDispatcher $notifier,
    ) {
    }

    /**
     * Doctors working at the facility on the given date, with their remaining
     * capacity based on the schedule set by the Facility Administrator.
     *
     * @return Collection<int, array{doctor: User, schedule: DoctorSchedule, booked: int, remaining: int, rating: float|null}>
     */
    public function availableDoctors(Facility $facility, Carbon $date): Collection
    {
        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->where('facility_id', $facility->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->whereHas('doctor', fn ($query) => $query->where('status', UserStatus::Active)->role(RoleName::Doctor->value))
            ->get();

        return $schedules->map(function (DoctorSchedule $schedule) use ($date) {
            $booked = Appointment::query()
                ->where('doctor_id', $schedule->doctor_id)
                ->whereDate('appointment_date', $date)
                ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Approved])
                ->count();

            $rating = $schedule->doctor->reviews()->avg('rating');

            return [
                'doctor' => $schedule->doctor,
                'schedule' => $schedule,
                'booked' => $booked,
                'remaining' => max(0, $schedule->max_appointments - $booked),
                'rating' => $rating ? round((float) $rating, 1) : null,
            ];
        })->filter(fn (array $slot) => $slot['remaining'] > 0)->values();
    }

    /**
     * Book an appointment. When no doctor is chosen the system allocates the
     * available doctor with the most remaining capacity.
     */
    public function book(Patient $patient, Facility $facility, Carbon $date, ?int $doctorId, string $reason): Appointment
    {
        if ($date->isPast() && ! $date->isToday()) {
            throw new WorkflowException('Please choose today or a future date.');
        }

        return DB::transaction(function () use ($patient, $facility, $date, $doctorId, $reason) {
            $available = $this->availableDoctors($facility, $date);

            if ($available->isEmpty()) {
                throw new WorkflowException("No doctor is available at {$facility->name} on {$date->format('l j F Y')}. Please choose another date.");
            }

            $slot = $doctorId
                ? $available->first(fn (array $item) => $item['doctor']->id === $doctorId)
                : $available->sortByDesc('remaining')->first();

            if (! $slot) {
                throw new WorkflowException('The selected doctor is fully booked on that date. Please choose another doctor or date.');
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'facility_id' => $facility->id,
                'doctor_id' => $slot['doctor']->id,
                'appointment_date' => $date->toDateString(),
                'reason' => $reason,
                'status' => AppointmentStatus::Pending,
            ]);

            $this->notifyFacilityStaff($appointment);
            $this->audit->record('appointment.booked', "Appointment requested with {$slot['doctor']->name} on {$date->toFormattedDateString()}.", $appointment);

            return $appointment;
        });
    }

    public function decide(Appointment $appointment, AppointmentStatus $decision, ?string $note, User $staff): void
    {
        if ($appointment->status !== AppointmentStatus::Pending) {
            throw new WorkflowException('This appointment has already been decided.');
        }

        if (! in_array($decision, [AppointmentStatus::Approved, AppointmentStatus::Declined], true)) {
            throw new WorkflowException('Choose to approve or decline the appointment.');
        }

        $appointment->update([
            'status' => $decision,
            'decision_note' => $note,
            'decided_by' => $staff->id,
            'decided_at' => now(),
        ]);

        $this->notifier->toPatient($appointment->patient, new AppointmentDecisionNotification($appointment));
        $this->audit->record('appointment.'.$decision->value, "{$decision->label()} the appointment for {$appointment->patient->full_name}.", $appointment);
    }

    public function complete(Appointment $appointment): void
    {
        if ($appointment->status !== AppointmentStatus::Approved) {
            throw new WorkflowException('Only approved appointments can be marked as attended.');
        }

        $appointment->update(['status' => AppointmentStatus::Completed]);
        $this->audit->record('appointment.completed', "Marked the appointment for {$appointment->patient->full_name} as attended.", $appointment);
    }

    public function cancel(Appointment $appointment): void
    {
        if (! $appointment->canBeCancelled()) {
            throw new WorkflowException('This appointment can no longer be cancelled.');
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);
        $this->audit->record('appointment.cancelled', 'Appointment cancelled by the patient.', $appointment);
    }

    public function review(Appointment $appointment, int $rating, bool $wouldRecommend, ?string $comment): void
    {
        if (! $appointment->canBeReviewed()) {
            throw new WorkflowException('Only attended appointments can be rated, and only once.');
        }

        $appointment->review()->create([
            'doctor_id' => $appointment->doctor_id,
            'rating' => $rating,
            'would_recommend' => $wouldRecommend,
            'comment' => $comment,
        ]);
    }

    /**
     * Tell the allocated doctor and the facility administrators that a new
     * request is waiting for approval.
     */
    private function notifyFacilityStaff(Appointment $appointment): void
    {
        $recipients = User::query()
            ->active()
            ->where('facility_id', $appointment->facility_id)
            ->where(fn ($query) => $query
                ->whereKey($appointment->doctor_id)
                ->orWhereHas('roles', fn ($roles) => $roles->where('name', RoleName::FacilityAdmin->value)))
            ->get();

        try {
            Notification::send($recipients, new NewAppointmentRequestNotification($appointment));
        } catch (Throwable $exception) {
            Log::error('Appointment request notification failed.', ['appointment_id' => $appointment->id, 'error' => $exception->getMessage()]);
        }
    }
}
