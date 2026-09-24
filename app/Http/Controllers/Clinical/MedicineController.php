<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Medicine stock at the user's facility.
 */
class MedicineController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        return view('clinical.medicines.index', [
            'medicines' => Medicine::query()
                ->where('facility_id', $request->user()->facility_id)
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
                ->when($request->boolean('low_stock'), fn ($query) => $query->lowStock())
                ->orderBy('name')
                ->paginate($this->perPage())
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('clinical.medicines.form', ['medicine' => new Medicine, 'dosageForms' => $this->settings->list('dosage_forms')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $medicine = Medicine::create([...$this->validated($request), 'facility_id' => $request->user()->facility_id]);
        $this->audit->record('medicine.created', "Added {$medicine->displayName()} to the stock list.", $medicine);

        return redirect()->route('medicines.index')->with('success', "{$medicine->displayName()} has been added.");
    }

    public function edit(Medicine $medicine): View
    {
        $this->ensureOwnFacility($medicine);

        return view('clinical.medicines.form', ['medicine' => $medicine, 'dosageForms' => $this->settings->list('dosage_forms')]);
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->ensureOwnFacility($medicine);
        $medicine->update($this->validated($request, $medicine));
        $this->audit->record('medicine.updated', "Updated stock for {$medicine->displayName()}.", $medicine);

        return redirect()->route('medicines.index')->with('success', "{$medicine->displayName()} has been updated.");
    }

    private function validated(Request $request, ?Medicine $medicine = null): array
    {
        $facilityId = $request->user()->facility_id;

        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('medicines')
                ->where('facility_id', $facilityId)
                ->where('strength', $request->input('strength'))
                ->ignore($medicine)],
            'strength' => ['nullable', 'string', 'max:50'],
            'dosage_form' => ['required', Rule::in($this->settings->list('dosage_forms'))],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'expiry_date' => ['nullable', 'date'],
        ], ['name.unique' => 'This medicine and strength is already on the stock list.']);
    }

    private function ensureOwnFacility(Medicine $medicine): void
    {
        abort_unless($medicine->facility_id === request()->user()->facility_id, 403, 'This medicine belongs to another facility.');
    }
}
