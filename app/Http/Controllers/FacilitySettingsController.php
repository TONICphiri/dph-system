<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

/**
 * Facility system settings, maintained by the Facility Admin from
 * the admin dashboard: hospital name, contact details, address,
 * map link, working hours, logo, departments and services.
 * These values feed the public login page footer and info sections.
 */
class FacilitySettingsController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorize('manage_own_facility_settings');

        $facility = $this->targetFacility($request);

        // National Admins may switch between facilities; Facility
        // Admins only ever see their own location.
        $facilities = $this->user()->isNationalAdmin()
            ? Facility::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('settings.facility', compact('facility', 'facilities'));
    }

    public function update(Request $request)
    {
        $this->authorize('manage_own_facility_settings');

        $facility = $this->targetFacility($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'facility_type' => 'required|string|in:Hospital,Health Centre,Clinic',
            'district' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:2000',
            'phone_number' => 'nullable|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string|max:255',
            'map_url' => 'nullable|url|max:2000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'services' => 'nullable|string|max:5000',
            'departments' => 'nullable|string|max:5000',
        ]);

        $data = collect($validated)->except(['logo', 'services', 'departments'])->toArray();
        $data['services'] = $this->linesToArray($validated['services'] ?? null);
        $data['departments'] = $this->linesToArray($validated['departments'] ?? null);

        // Setting the facility logo is reserved for the national (main) admin.
        if ($request->hasFile('logo') && !$this->user()->isNationalAdmin()) {
            abort(403, 'Only the national admin can change the facility logo.');
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('facility-logos', 'public');
        }

        $facility->update($data);

        return redirect()->route('settings.facility.edit', $this->facilityQuery($facility))
            ->with('success', 'Facility profile updated. The login page now shows the new details.');
    }

    /**
     * Facility Admins may only edit their own facility. National
     * Admins may pick any facility via ?facility=ID.
     */
    protected function targetFacility(Request $request): Facility
    {
        $me = $this->user();

        if ($me->isNationalAdmin() && $request->filled('facility')) {
            return Facility::findOrFail($request->integer('facility'));
        }

        abort_unless($me->facility_id, 403, 'Your account is not attached to a facility.');

        return Facility::findOrFail($me->facility_id);
    }

    protected function facilityQuery(Facility $facility): array
    {
        return $this->user()->isNationalAdmin() ? ['facility' => $facility->id] : [];
    }

    /**
     * One item per line from the textarea → JSON array for storage.
     */
    protected function linesToArray(?string $text): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $lines = array_values(array_filter(array_map(
            fn ($line) => trim($line),
            preg_split('/\r\n|\r|\n/', $text)
        )));

        return $lines === [] ? null : $lines;
    }
}
