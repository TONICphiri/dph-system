<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacilityController extends Controller
{
    public function index()
    {
        $this->authorize('manage_facility');

        $facilities = Facility::withCount([
            'users as total_staff',
            'users as active_staff' => function ($query) {
                $query->where('status', 'active');
            },
        ])->orderBy('name')->paginate(15);

        $totalFacilityStaff = User::query()->count();
        $activeFacilityStaff = User::query()->where('status', 'active')->count();

        return view('admin.facilities.index', [
            'facilities' => $facilities,
            'totalFacilityStaff' => $totalFacilityStaff,
            'activeFacilityStaff' => $activeFacilityStaff,
        ]);
    }

    public function create()
    {
        $this->authorize('manage_facility');

        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manage_facility');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name',
            'facility_code' => 'required|string|max:255|unique:facilities,facility_code',
            'facility_type' => 'required|string|in:Hospital,Health Centre,Clinic',
            'district' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();
            Facility::create($validated);
            DB::commit();

            return redirect()->route('facilities.index')->with('success', 'Facility created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Facility creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to create facility. Please try again.');
        }
    }

    public function edit(Facility $facility)
    {
        $this->authorize('manage_facility');

        return view('admin.facilities.edit', compact('facility'));
    }

    public function update(Request $request, Facility $facility)
    {
        $this->authorize('manage_facility');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name,' . $facility->id,
            'facility_code' => 'required|string|max:255|unique:facilities,facility_code,' . $facility->id,
            'facility_type' => 'required|string|in:Hospital,Health Centre,Clinic',
            'district' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);

        try {
            $facility->update($validated);

            return redirect()->route('facilities.index')->with('success', 'Facility updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Facility update failed', ['error' => $e->getMessage(), 'facility_id' => $facility->id]);

            return redirect()->back()->with('error', 'Failed to update facility. Please try again.');
        }
    }

    public function destroy(Facility $facility)
    {
        $this->authorize('manage_facility');

        try {
            $facility->delete();

            return redirect()->route('facilities.index')->with('success', 'Facility deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Facility deletion failed', ['error' => $e->getMessage(), 'facility_id' => $facility->id]);

            return redirect()->back()->with('error', 'Failed to delete facility. Please try again.');
        }
    }
}
