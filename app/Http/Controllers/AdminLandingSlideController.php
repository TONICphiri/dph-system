<?php

namespace App\Http\Controllers;

use App\Models\LandingSlide;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Landing hero slideshow management. Main (national) admin ONLY —
 * requires manage_global_settings, which the facility admin does not have.
 */
class AdminLandingSlideController extends Controller
{
    public function index()
    {
        $this->authorize('manage_global_settings');

        $slides = LandingSlide::orderBy('sort_order')->orderBy('id')->get();
        $interval = (int) Setting::get('landing_slide_interval', 5);

        return view('admin.landing-slides.index', compact('slides', 'interval'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage_global_settings');

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('landing-slides', 'public');

        $slide = LandingSlide::create([
            'image_path' => $path,
            'caption' => $validated['caption'] ?? null,
            'sort_order' => (int) (LandingSlide::max('sort_order') ?? 0) + 1,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        AuditService::log($request->user(), 'landing_slide.created', $slide, []);

        return back()->with('success', 'Slide image added. It will appear on the landing page.');
    }

    public function update(Request $request, LandingSlide $slide)
    {
        $this->authorize('manage_global_settings');

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slide->update([
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $slide->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        AuditService::log($request->user(), 'landing_slide.updated', $slide, []);

        return back()->with('success', 'Slide updated.');
    }

    public function destroy(Request $request, LandingSlide $slide)
    {
        $this->authorize('manage_global_settings');

        if (! str_starts_with($slide->image_path, 'images/')) {
            Storage::disk('public')->delete($slide->image_path);
        }

        AuditService::log($request->user(), 'landing_slide.deleted', $slide, []);
        $slide->delete();

        return back()->with('success', 'Slide removed.');
    }

    /** How many seconds each image shows before the next appears. */
    public function updateInterval(Request $request)
    {
        $this->authorize('manage_global_settings');

        $validated = $request->validate([
            'landing_slide_interval' => ['required', 'integer', 'min:2', 'max:60'],
        ]);

        Setting::set('landing_slide_interval', $validated['landing_slide_interval'], 'landing');

        return back()->with('success', 'Slideshow interval saved.');
    }
}
