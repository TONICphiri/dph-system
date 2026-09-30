<?php

namespace App\Http\Controllers\Dhp\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Facility;
use App\Services\DhpAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DHP facility administration. No deletions: deactivation preserves
 * history while blocking new issuance and verification for that site.
 */
class FacilityController extends Controller
{
    public const TYPES = ['health_centre', 'district_hospital', 'central_hospital', 'laboratory', 'other'];

    public function index(Request $request): View
    {
        $facilities = Facility::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.trim($request->input('search')).'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('district_id', District::query()->where('name', 'like', $term)->select('id')));
            })
            ->when($request->filled('type') && in_array($request->input('type'), self::TYPES, true),
                fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->with('district')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dhp.admin.facilities.index', [
            'facilities' => $facilities,
            'types' => self::TYPES,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('dhp.admin.facilities.form', [
            'facility' => new Facility(['type' => 'health_centre', 'is_active' => true]),
            'types' => self::TYPES,
            'districts' => District::query()->orderBy('name')->pluck('name'),
            'method' => 'POST',
            'action' => route('dhp.admin.facilities.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $facility = Facility::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $data['name']), 0, 3)).'-'.random_int(100, 999),
            'type' => $data['type'],
            'ownership' => 'Government',
            'district_id' => District::query()->where('name', $data['district'])->value('id'),
            'status' => 'active',
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'facility_created',
            entityType: 'facility',
            entityId: $facility->id,
            details: ['facility_type' => $facility->type, 'is_active' => $facility->is_active],
            ipAddress: $request->ip(),
        );

        return redirect()->route('dhp.admin.facilities.index')->with('success', 'Facility created.');
    }

    public function edit(Facility $facility): View
    {
        return view('dhp.admin.facilities.form', [
            'facility' => $facility,
            'types' => self::TYPES,
            'districts' => District::query()->orderBy('name')->pluck('name'),
            'method' => 'PUT',
            'action' => route('dhp.admin.facilities.update', $facility),
        ]);
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $this->validated($request, $facility->id);
        $wasActive = $facility->is_active;

        $facility->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'district_id' => District::query()->where('name', $data['district'])->value('id'),
            'status' => ! empty($data['is_active']) ? 'active' : 'inactive',
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'facility_updated',
            entityType: 'facility',
            entityId: $facility->id,
            details: ['facility_type' => $facility->type, 'is_active' => $facility->is_active],
            ipAddress: $request->ip(),
        );

        if ($wasActive !== $facility->is_active) {
            DhpAuditLogger::log(
                user: $request->user(),
                action: $facility->is_active ? 'facility_activated' : 'facility_deactivated',
                entityType: 'facility',
                entityId: $facility->id,
                details: ['facility_type' => $facility->type, 'is_active' => $facility->is_active],
                ipAddress: $request->ip(),
            );
        }

        return redirect()->route('dhp.admin.facilities.index')->with('success', 'Facility updated.');
    }

    public function toggleActive(Request $request, Facility $facility): RedirectResponse
    {
        $facility->update([
            'is_active' => ! $facility->is_active,
            'status' => ! $facility->is_active ? 'active' : 'inactive',
        ]);

        DhpAuditLogger::log(
            user: $request->user(),
            action: $facility->is_active ? 'facility_activated' : 'facility_deactivated',
            entityType: 'facility',
            entityId: $facility->id,
            details: ['facility_type' => $facility->type, 'is_active' => $facility->is_active],
            ipAddress: $request->ip(),
        );

        return back()->with('success', $facility->is_active
            ? 'Facility activated.'
            : 'Facility deactivated. Its credentials stay stored but verify as Invalid.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:facilities,name'.($ignoreId ? ','.$ignoreId : '')],
            'code' => ['nullable', 'string', 'max:30', 'unique:facilities,code'.($ignoreId ? ','.$ignoreId : '')],
            'district' => ['required', 'string', 'exists:districts,name'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
