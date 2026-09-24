<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    protected $fillable = ['facility_id', 'doctor_id', 'day_of_week', 'start_time', 'end_time', 'max_appointments'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Day names in ISO order, where Monday is 1 and Sunday is 7.
     *
     * @return array<int, string>
     */
    public static function dayNames(): array
    {
        return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    }

    public function dayName(): string
    {
        return self::dayNames()[$this->day_of_week] ?? '';
    }

    public function hours(): string
    {
        return substr($this->start_time, 0, 5).' to '.substr($this->end_time, 0, 5);
    }
}
