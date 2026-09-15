<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * National-level Digital Passport configuration. Only the National
 * Admin (global read/write, no facility constraint) may change:
 * which facility profile the public login page shows, standardized
 * passport medical fields, vaccine categories and health templates.
 */
class GlobalSettingsController extends Controller
{
    public const KEYS = [
        'display_facility_id' => ['group' => 'global', 'label' => 'Display facility'],
        'passport_fields' => ['group' => 'passport', 'label' => 'Passport medical fields'],
        'vaccine_categories' => ['group' => 'vaccines', 'label' => 'Vaccine categories'],
        'health_templates' => ['group' => 'templates', 'label' => 'Health data templates'],
    ];

    public function edit()
    {
        $this->authorize('manage_global_settings');

        $facilities = Facility::where('status', 'active')->orderBy('name')->get();
        $values = [
            'display_facility_id' => Setting::get('display_facility_id'),
            'passport_fields' => $this->toLines(Setting::get('passport_fields', [])),
            'vaccine_categories' => $this->toLines(Setting::get('vaccine_categories', [])),
            'health_templates' => $this->toLines(Setting::get('health_templates', [])),
        ];

        return view('settings.global', compact('facilities', 'values'));
    }

    public function update(Request $request)
    {
        $this->authorize('manage_global_settings');

        $validated = $request->validate([
            'display_facility_id' => 'nullable|exists:facilities,id',
            'passport_fields' => 'nullable|string|max:10000',
            'vaccine_categories' => 'nullable|string|max:10000',
            'health_templates' => 'nullable|string|max:10000',
        ]);

        Setting::set('display_facility_id', $validated['display_facility_id'] ?? null, 'global');
        Setting::set('passport_fields', $this->linesToArray($validated['passport_fields'] ?? null) ?? [], 'passport');
        Setting::set('vaccine_categories', $this->linesToArray($validated['vaccine_categories'] ?? null) ?? [], 'vaccines');
        Setting::set('health_templates', $this->linesToArray($validated['health_templates'] ?? null) ?? [], 'templates');

        return redirect()->route('settings.global.edit')
            ->with('success', 'National configuration saved.');
    }

    protected function toLines(mixed $value): string
    {
        if (is_array($value)) {
            return implode("\n", $value);
        }

        return is_string($value) ? $value : '';
    }

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
