<?php

namespace App\Http\Controllers\Facility;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Weekly doctor schedules. Appointment booking uses these to check which
 * doctors are available on a given day and how many patients they can see.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $facilityId = $request->user()->facility_id;

        return view('facility.schedules.index', [
            'schedules' => DoctorSchedule::query()->with('doctor')->where('facility_id', $facilityId)
                ->orderBy('day_of_week')->orderBy('start_time')->get()->groupBy('day_of_week'),
            'doctors' => User::query()->where('facility_id', $facilityId)->withRole(RoleName::Doctor)->active()->orderBy('name')->get(),
            'days' => DoctorSchedule::dayNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;

        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('facility_id', $facilityId)],
            'day_of_week' => ['required', 'integer', 'between:1,7', Rule::unique('doctor_schedules')->where('doctor_id', $request->input('doctor_id'))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'max_appointments' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'day_of_week.unique' => 'This doctor already has a schedule on that day. Remove it first to change it.',
            'end_time.after' => 'The end time must be later than the start time.',
        ], ['doctor_id' => 'doctor', 'day_of_week' => 'day']);

        DoctorSchedule::create([...$data, 'facility_id' => $facilityId]);

        return back()->with('success', 'The schedule has been added.');
    }

    public function destroy(Request $request, DoctorSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->facility_id === $request->user()->facility_id, 403);
        $schedule->delete();

        return back()->with('success', 'The schedule has been removed.');
    }
}
