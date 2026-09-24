<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignAudience;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\HealthCampaign;
use App\Services\CampaignService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Health campaign messages. The System Administrator sends national
 * campaigns; a Facility Administrator sends to patients registered at their
 * facility.
 */
class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('campaigns.index', [
            'campaigns' => HealthCampaign::query()
                ->with(['facility', 'createdBy'])
                ->when(! $user->isRole(RoleName::SystemAdmin), fn ($query) => $query->where('facility_id', $user->facility_id))
                ->latest()
                ->paginate($this->perPage()),
        ]);
    }

    public function create(): View
    {
        return view('campaigns.create', [
            'categories' => $this->settings->list('campaign_categories'),
            'audiences' => CampaignAudience::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in($this->settings->list('campaign_categories'))],
            'audience' => ['required', Rule::enum(CampaignAudience::class)],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $campaign = HealthCampaign::create([
            ...$data,
            'facility_id' => $user->isRole(RoleName::SystemAdmin) ? null : $user->facility_id,
            'created_by' => $user->id,
        ]);

        if ($request->boolean('publish_now')) {
            $count = $this->campaigns->publish($campaign);

            return redirect()->route('campaigns.index')->with('success', "The campaign has been sent to {$count} patients.");
        }

        return redirect()->route('campaigns.index')->with('success', 'The campaign has been saved as a draft.');
    }

    public function publish(Request $request, HealthCampaign $campaign): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isRole(RoleName::SystemAdmin) || $campaign->facility_id === $user->facility_id, 403);

        $count = $this->campaigns->publish($campaign);

        return back()->with('success', "The campaign has been sent to {$count} patients.");
    }
}
