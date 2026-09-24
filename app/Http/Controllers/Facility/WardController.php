<?php

namespace App\Http\Controllers\Facility;

use App\Enums\BedStatus;
use App\Enums\FacilityStatus;
use App\Enums\WardGender;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WardController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        return view('facility.wards.index', [
            'wards' => Ward::query()->where('facility_id', $request->user()->facility_id)
                ->withCount([
                    'beds',
                    'beds as available_count' => fn ($query) => $query->where('status', BedStatus::Available),
                    'beds as occupied_count' => fn ($query) => $query->where('status', BedStatus::Occupied),
                ])->orderBy('name')->get(),
        ]);
    }

    /**
     * Live view of every bed at the facility, for nurses and doctors.
     */
    public function board(Request $request): View
    {
        return view('facility.wards.board', [
            'wards' => Ward::query()->where('facility_id', $request->user()->facility_id)
                ->with(['beds.currentAdmission.patient'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('facility.wards.form', $this->formData(new Ward(['gender_restriction' => WardGender::Mixed])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['bed_count'] = $request->validate(['bed_count' => ['required', 'integer', 'min:1', 'max:200']])['bed_count'];

        $ward = Ward::create([
            ...collect($data)->except('bed_count')->all(),
            'facility_id' => $request->user()->facility_id,
            'status' => FacilityStatus::Active,
        ]);

        $this->createBeds($ward, (int) $data['bed_count']);
        $this->audit->record('ward.created', "Created {$ward->name} with {$data['bed_count']} beds.", $ward);

        return redirect()->route('facility.wards.show', $ward)->with('success', "{$ward->name} has been created.");
    }

    public function show(Ward $ward): View
    {
        $this->ensureOwnFacility($ward);

        return view('facility.wards.show', [
            'ward' => $ward->load('beds.currentAdmission.patient'),
            'bedStatuses' => BedStatus::options(),
        ]);
    }

    public function edit(Ward $ward): View
    {
        $this->ensureOwnFacility($ward);

        return view('facility.wards.form', $this->formData($ward));
    }

    public function update(Request $request, Ward $ward): RedirectResponse
    {
        $this->ensureOwnFacility($ward);

        $data = $this->validated($request, $ward);
        $data['status'] = $request->validate(['status' => ['required', Rule::enum(FacilityStatus::class)]])['status'];

        $ward->update($data);
        $this->audit->record('ward.updated', "Updated {$ward->name}.", $ward);

        return redirect()->route('facility.wards.show', $ward)->with('success', 'The ward has been updated.');
    }

    public function addBeds(Request $request, Ward $ward): RedirectResponse
    {
        $this->ensureOwnFacility($ward);
        $count = (int) $request->validate(['bed_count' => ['required', 'integer', 'min:1', 'max:100']])['bed_count'];

        $this->createBeds($ward, $count);

        return back()->with('success', "{$count} beds have been added to {$ward->name}.");
    }

    public function updateBed(Request $request, Bed $bed): RedirectResponse
    {
        $this->ensureOwnFacility($bed->ward);

        $status = BedStatus::from($request->validate([
            'status' => ['required', Rule::in([BedStatus::Available->value, BedStatus::Maintenance->value])],
        ])['status']);

        if ($bed->status === BedStatus::Occupied) {
            throw new WorkflowException("Bed {$bed->bed_number} is occupied. Discharge or move the patient first.");
        }

        $bed->update(['status' => $status]);

        return back()->with('success', "Bed {$bed->bed_number} is now {$status->label()}.");
    }

    private function createBeds(Ward $ward, int $count): void
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $ward->name), 0, 1)) ?: 'B';
        $next = $ward->beds()->count() + 1;

        for ($index = 0; $index < $count; $index++) {
            $number = $prefix.($next + $index);

            while ($ward->beds()->where('bed_number', $number)->exists()) {
                $next++;
                $number = $prefix.($next + $index);
            }

            $ward->beds()->create(['bed_number' => $number, 'status' => BedStatus::Available]);
        }
    }

    private function validated(Request $request, ?Ward $ward = null): array
    {
        $facilityId = $request->user()->facility_id;

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('wards')->where('facility_id', $facilityId)->ignore($ward)],
            'ward_type' => ['required', Rule::in($this->settings->list('ward_types'))],
            'gender_restriction' => ['required', Rule::enum(WardGender::class)],
        ]);
    }

    private function formData(Ward $ward): array
    {
        return [
            'ward' => $ward,
            'wardTypes' => $this->settings->list('ward_types'),
            'genders' => WardGender::options(),
            'statuses' => FacilityStatus::options(),
        ];
    }

    private function ensureOwnFacility(Ward $ward): void
    {
        abort_unless($ward->facility_id === request()->user()->facility_id, 403, 'This ward belongs to another facility.');
    }
}
